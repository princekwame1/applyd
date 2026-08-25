<?php

namespace App\Http\Controllers;

use App\Exports\CourseEnrollmentsExport;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Services\PaymentReminderService;
use App\Services\SmsNotificationService;
use App\Services\StudentAccountService;
use App\Support\Paystack;
use App\Support\PaystackFees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CourseEnrollmentController extends Controller
{
    public function adminIndex()
    {
        return view('dashboard.course-registrations', [
            'stats' => [
                'total' => CourseEnrollment::count(),
                'completed' => CourseEnrollment::whereNotNull('completed_at')->count(),
                'form_revenue' => CourseEnrollment::where('status', 'paid')->sum('amount'),
                'tuition_revenue' => CourseEnrollment::sum('tuition_amount'),
                // The two payments this screen chases, counted separately —
                // "paid" on its own never said which money was meant.
                'form_paid' => CourseEnrollment::formPaid()->count(),
                'form_unpaid' => CourseEnrollment::formUnpaid()->count(),
                'tuition_paid' => CourseEnrollment::tuitionPaid()->count(),
                'tuition_outstanding' => CourseEnrollment::tuitionOutstanding()->count(),
                'credentials_sent' => CourseEnrollment::whereNotNull('credentials_sent_at')->count(),
                // Finished registering but never got their login — the set the
                // resend action exists for.
                'awaiting_credentials' => CourseEnrollment::whereNotNull('completed_at')
                    ->whereNull('credentials_sent_at')
                    ->count(),
            ],
        ]);
    }

    public function export()
    {
        return Excel::download(new CourseEnrollmentsExport, 'course-registrations-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Issue (or re-issue) a student's portal login from the dashboard — for
     * when an SMS bounced, or the account couldn't be created at the time.
     *
     * Only for a finished registration: the ID marks someone as enrolled, and
     * handing one out mid-application would say something that isn't true yet.
     */
    public function resendCredentials(Request $request, CourseEnrollment $enrollment)
    {
        if (! $enrollment->is_completed) {
            return back()->with('error', 'This registration isn\'t complete yet, so there\'s no student ID to issue.');
        }

        $result = app(StudentAccountService::class)->resendCredentials($enrollment);

        return back()->with('status', $result['reset']
            ? 'Sent — student ID '.$result['student_id'].' with a new temporary password. Any password issued before now no longer works.'
            : 'Sent — student ID '.$result['student_id'].'. They have already set their own password, so it was left alone.');
    }

    /**
     * The destination of every payment-reminder SMS: one link per student that
     * always lands them on whatever they still owe.
     *
     * Deliberately one route rather than two. A reminder is sent days before it
     * is opened, and by then the student may have paid the form fee from the
     * original tab — so what the link should do is decided when it is FOLLOWED,
     * not when it was written.
     *
     * The token is the credential, the same way the Serial No and PIN are: both
     * travel to the same phone by the same SMS and open the same application.
     */
    public function pay(Request $request, string $token)
    {
        $enrollment = CourseEnrollment::with('course')->where('pay_token', $token)->first();

        abort_unless($enrollment, 404);

        // Form fee outstanding: straight back to checkout for THIS row, so the
        // registration they already started is the one that gets paid rather
        // than a duplicate created by filling the form in again.
        if ($enrollment->owesFormFee()) {
            return $this->reopenFormFeeCheckout($enrollment);
        }

        // Form fee settled — sign them into their own application and let the
        // page decide what is left: details to finish, or tuition to pay.
        $request->session()->put('applicant_id', $enrollment->id);

        return redirect()->route('application.complete');
    }

    /**
     * Re-open Paystack for a form fee that was never paid.
     *
     * A fresh reference every time on purpose: Paystack ties a reference to one
     * transaction attempt, and reusing the abandoned one is refused. `pay_token`
     * is what keeps the texted link stable while the reference moves underneath.
     */
    protected function reopenFormFeeCheckout(CourseEnrollment $enrollment)
    {
        $course = $enrollment->course;

        if (! $course) {
            abort(404);
        }

        if (! Paystack::configured()) {
            return redirect()->route('courses.show', $course)
                ->with('enroll_error', 'Online payment is not available right now. Please contact us to complete your registration.');
        }

        // What they were quoted when they registered, not today's price — the
        // fee may have been changed since, and this is a bill already issued.
        $amount = (float) $enrollment->amount;
        $charged = PaystackFees::gross($amount);
        $reference = 'CRS-'.$course->id.'-'.strtoupper(Str::random(10));

        $init = Paystack::initialize([
            'email' => $enrollment->email,
            'amount' => PaystackFees::pesewas($charged),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('courses.enroll.callback'),
            'metadata' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'name' => $enrollment->name,
                'phone' => $enrollment->phone,
                'base_amount' => $amount,
            ],
        ]);

        if (empty($init['status']) || empty($init['data']['authorization_url'])) {
            return redirect()->route('courses.show', $course)
                ->with('enroll_error', 'We could not re-open the payment. Please try again in a moment.');
        }

        // Only now that checkout is open: the callback finds the row by
        // reference, so moving it before a failed init would strand the row.
        $enrollment->update([
            'reference' => $reference,
            'amount_fee' => PaystackFees::fee($amount),
            'status' => 'pending',
        ]);

        return redirect()->away($init['data']['authorization_url']);
    }

    /** Text one student a reminder to pay their application form fee. */
    public function remindFormFee(Request $request, CourseEnrollment $enrollment, PaymentReminderService $reminders)
    {
        if (! $enrollment->owesFormFee()) {
            return back()->with('error', $enrollment->name.' has already paid the form fee — nothing sent.');
        }

        $sent = $reminders->sendFormFeeReminder($enrollment);

        return back()->with(
            $sent ? 'status' : 'error',
            $sent
                ? 'Form-fee reminder sent to '.$enrollment->name.' on '.$enrollment->phone.'.'
                : 'Could not send the reminder to '.$enrollment->name.'. Check SMS Delivery for the reason.'
        );
    }

    /** Text one student a reminder to pay their outstanding tuition. */
    public function remindTuition(Request $request, CourseEnrollment $enrollment, PaymentReminderService $reminders)
    {
        $enrollment->loadMissing('course');

        if (! $enrollment->owesTuition()) {
            return back()->with('error', $enrollment->name.' has no tuition outstanding — nothing sent.');
        }

        $sent = $reminders->sendTuitionReminder($enrollment);

        return back()->with(
            $sent ? 'status' : 'error',
            $sent
                ? 'Tuition reminder sent to '.$enrollment->name.' on '.$enrollment->phone.'.'
                : 'Could not send the reminder to '.$enrollment->name.'. Check SMS Delivery for the reason.'
        );
    }

    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        if (! Paystack::configured()) {
            return back()->with('enroll_error', 'Online payment is not available right now. Please contact us to register.')->withInput();
        }

        $amount = (float) $course->form_fee;
        // The charge is added on top, grossed up so the form fee itself still
        // lands in full. `amount` stays the fee; the charge is its own column.
        $charged = PaystackFees::gross($amount);
        $reference = 'CRS-'.$course->id.'-'.strtoupper(Str::random(10));

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'amount' => $amount,
            'amount_fee' => PaystackFees::fee($amount),
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $init = Paystack::initialize([
            'email' => $data['email'],
            'amount' => PaystackFees::pesewas($charged),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('courses.enroll.callback'),
            'metadata' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'name' => $data['name'],
                'phone' => $data['phone'],
                // What this payment is worth to us, before the charge. Read
                // back on the callback so a balance is never credited with
                // money that went to Paystack.
                'base_amount' => $amount,
            ],
        ]);

        if (empty($init['status']) || empty($init['data']['authorization_url'])) {
            $enrollment->update(['status' => 'failed']);

            return back()->with('enroll_error', 'We could not start the payment. Please try again.')->withInput();
        }

        return redirect()->away($init['data']['authorization_url']);
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference', $request->query('trxref'));
        $enrollment = CourseEnrollment::with('course')->where('reference', $reference)->first();

        abort_unless($enrollment, 404);

        $paid = false;

        if (Paystack::configured()) {
            $verify = Paystack::verify($reference);
            $paid = ! empty($verify['status']) && ($verify['data']['status'] ?? null) === 'success';
        }

        if ($paid && $enrollment->status !== 'paid') {
            $enrollment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'serial_no' => $enrollment->serial_no ?: CourseEnrollment::generateSerial(),
                'pin' => $enrollment->pin ?: CourseEnrollment::generatePin(),
            ]);

            $this->sendApplicationNotifications($enrollment->fresh('course'));
        } elseif (! $paid && $enrollment->status !== 'paid') {
            $enrollment->update(['status' => 'failed']);
        }

        return view('course-enrolled', [
            'enrollment' => $enrollment->fresh('course'),
            'success' => $enrollment->fresh()->status === 'paid',
        ]);
    }

    protected function sendApplicationNotifications(CourseEnrollment $enrollment): void
    {
        $course = $enrollment->course?->title ?? 'course';
        $link = route('application.login');

        // SMS
        $message = "Dear {$enrollment->first_name}, You have started your {$course} application with SNo:{$enrollment->serial_no} and PIN:{$enrollment->pin}. Ensure you complete all stages of the application. Continue: {$link}";

        try {
            app(SmsNotificationService::class)->send($enrollment->phone, $message, null, $enrollment->name);
        } catch (\Throwable $e) {
            report($e);
        }

        // Email copy of the credentials
        try {
            Mail::send('emails.application-started', [
                'firstName' => $enrollment->first_name,
                'courseTitle' => $course,
                'serialNo' => $enrollment->serial_no,
                'pin' => $enrollment->pin,
                'link' => $link,
            ], function ($mail) use ($enrollment, $course) {
                $mail->to($enrollment->email, $enrollment->name)
                    ->subject('Your '.$course.' application — Serial No & PIN');
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* -------------------- Applicant portal -------------------- */

    public function loginForm()
    {
        if (session('applicant_id')) {
            return redirect()->route('application.complete');
        }

        return view('application.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'serial_no' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $enrollment = CourseEnrollment::where('serial_no', trim($data['serial_no']))
            ->where('pin', trim($data['pin']))
            ->where('status', 'paid')
            ->first();

        if (! $enrollment) {
            return back()->withErrors(['serial_no' => 'Invalid Serial Number or PIN.'])->onlyInput('serial_no');
        }

        $request->session()->put('applicant_id', $enrollment->id);

        return redirect()->route('application.complete');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('applicant_id');

        return redirect()->route('application.login')->with('status', 'You have been signed out.');
    }

    public function complete(Request $request)
    {
        $enrollment = $this->currentApplicant($request);

        return view('application.complete', ['enrollment' => $enrollment]);
    }

    public function submit(Request $request)
    {
        $enrollment = $this->currentApplicant($request);

        $data = $request->validate([
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'education_level' => ['required', 'string', 'max:100'],
            'goals' => ['nullable', 'string', 'max:2000'],
        ]);

        $enrollment->update($data);
        $enrollment = $enrollment->fresh('course');

        // Free course (no tuition) — registration completes right away.
        if (! ($enrollment->course && $enrollment->course->requiresTuition())) {
            if ($enrollment->completed_at === null) {
                $enrollment->update(['completed_at' => now(), 'tuition_status' => 'paid']);
                $this->completeRegistration($enrollment);
            }

            return redirect()->route('application.complete')->with('status', 'Your registration is complete!');
        }

        return redirect()->route('application.complete')->with('status', 'Details saved. Complete your tuition payment to finish your registration.');
    }

    /**
     * Start a tuition payment (full, 50%, or the outstanding balance) via Paystack.
     */
    public function tuitionInit(Request $request)
    {
        $enrollment = $this->currentApplicant($request);
        $course = $enrollment->course;

        if (! $course || ! $course->requiresTuition()) {
            return redirect()->route('application.complete');
        }
        if (! $enrollment->hasDetails()) {
            return redirect()->route('application.complete')->with('enroll_error', 'Please complete your application details first.');
        }

        $option = $request->input('option');
        $offeredKeys = array_column($course->attendanceOptions(), 'key');

        $rules = ['option' => ['required', Rule::in(['full', 'half', 'balance'])]];
        if ($option !== 'balance') {
            $rules['attendance_type'] = ['required', Rule::in($offeredKeys)];
        }
        $validated = $request->validate($rules);

        if (! Paystack::configured()) {
            return redirect()->route('application.complete')->with('enroll_error', 'Online payment is not available right now. Please try again later.');
        }

        // Lock in the chosen attendance mode (drives the price).
        if ($option !== 'balance') {
            $enrollment->update(['attendance_type' => $validated['attendance_type']]);
            $enrollment = $enrollment->fresh('course');
        }

        $full = $enrollment->tuitionFull();
        $amount = match ($validated['option']) {
            'full' => $full,
            'half' => round($full / 2, 2),
            'balance' => $enrollment->tuitionBalance(),
        };

        if ($amount <= 0) {
            return redirect()->route('application.complete');
        }

        $reference = 'TUI-'.$enrollment->id.'-'.strtoupper(Str::random(8));

        $enrollment->update([
            'tuition_option' => $validated['option'],
            'tuition_reference' => $reference,
            'tuition_status' => 'pending',
        ]);

        $init = Paystack::initialize([
            'email' => $enrollment->email,
            'amount' => PaystackFees::pesewas(PaystackFees::gross($amount)),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('application.tuition.callback'),
            'metadata' => [
                'type' => 'tuition',
                'enrollment_id' => $enrollment->id,
                'course_title' => $course->title,
                'option' => $validated['option'],
                // The tuition this instalment covers, before the charge — the
                // figure the balance has to move by.
                'base_amount' => $amount,
            ],
        ]);

        if (empty($init['status']) || empty($init['data']['authorization_url'])) {
            $enrollment->update(['tuition_status' => $enrollment->tuition_amount > 0 ? 'partial' : 'unpaid']);

            return redirect()->route('application.complete')->with('enroll_error', 'We could not start the payment. Please try again.');
        }

        return redirect()->away($init['data']['authorization_url']);
    }

    public function tuitionCallback(Request $request)
    {
        $reference = $request->query('reference', $request->query('trxref'));
        $enrollment = CourseEnrollment::with('course')->where('tuition_reference', $reference)->first();

        abort_unless($enrollment, 404);

        $verify = Paystack::configured() ? Paystack::verify($reference) : ['status' => false];
        $success = ! empty($verify['status']) && ($verify['data']['status'] ?? null) === 'success';

        if ($success) {
            $charged = (float) (($verify['data']['amount'] ?? 0) / 100);
            // What they were charged includes the Paystack fee. Only the
            // tuition part counts against the balance, or a fully-paid student
            // would read as having overpaid.
            $paidNow = $this->netPaid($verify, $charged);
            $newTotal = round($enrollment->tuition_amount + $paidNow, 2);
            $full = $enrollment->course?->tuition_full ?? 0;
            $status = ($newTotal + 0.01) >= $full ? 'paid' : 'partial';
            $firstCompletion = $enrollment->completed_at === null;

            $enrollment->update([
                'tuition_amount' => $newTotal,
                'tuition_fee' => round((float) $enrollment->tuition_fee + max($charged - $paidNow, 0), 2),
                'tuition_status' => $status,
                'tuition_paid_at' => now(),
                'completed_at' => $enrollment->completed_at ?? now(),
            ]);

            if ($firstCompletion) {
                $this->completeRegistration($enrollment->fresh('course'));
            }
        } else {
            $enrollment->update(['tuition_status' => $enrollment->tuition_amount > 0 ? 'partial' : 'unpaid']);
        }

        return redirect()->route('application.complete')->with(
            $success ? 'status' : 'enroll_error',
            $success ? 'Payment received. Thank you!' : 'We could not confirm your tuition payment. Please try again.'
        );
    }

    /**
     * The part of a verified payment that is ours, with the Paystack charge
     * taken back off.
     *
     * The base travels in the transaction metadata, because that is exact.
     * Inverting the charged figure is the fallback for anything started before
     * the fee was passed on, or where metadata didn't come back.
     */
    protected function netPaid(array $verify, float $charged): float
    {
        $base = $verify['data']['metadata']['base_amount'] ?? null;

        if (is_numeric($base) && (float) $base > 0 && (float) $base <= $charged + 0.01) {
            return round((float) $base, 2);
        }

        return PaystackFees::netFrom($charged);
    }

    /**
     * Registration is finished. This is the single place that happens — a free
     * course lands here from the details step, a paid one from the tuition
     * callback — so it is also where the student gets their ID and a login.
     */
    protected function completeRegistration(CourseEnrollment $enrollment): void
    {
        $course = $enrollment->course?->title ?? 'course';

        $message = "Dear {$enrollment->first_name}, your registration for {$course} has been completed successfully. Further communication regarding the next steps will be sent to you. Thank you for choosing Applyd Academy.";

        try {
            app(SmsNotificationService::class)->send($enrollment->phone, $message, null, $enrollment->name);
        } catch (\Throwable $e) {
            report($e);
        }

        // Issuing the account is wrapped too: a paid, completed registration
        // must stand even if the account can't be created this second. The
        // admin can issue and resend from the dashboard.
        try {
            app(StudentAccountService::class)->issueAndNotify($enrollment);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function currentApplicant(Request $request): CourseEnrollment
    {
        $id = $request->session()->get('applicant_id');
        $enrollment = $id ? CourseEnrollment::with('course')->find($id) : null;

        abort_if(! $enrollment, 403);

        return $enrollment;
    }
}
