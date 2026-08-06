<?php

namespace App\Http\Controllers;

use App\Exports\CourseEnrollmentsExport;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Support\Paystack;
use Illuminate\Http\Request;
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
            ],
        ]);
    }

    public function export()
    {
        return Excel::download(new CourseEnrollmentsExport, 'course-registrations-'.now()->format('Y-m-d').'.xlsx');
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

        $amount = $course->form_fee;
        $reference = 'CRS-'.$course->id.'-'.strtoupper(Str::random(10));

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $init = Paystack::initialize([
            'email' => $data['email'],
            'amount' => (int) round($amount * 100), // pesewas
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('courses.enroll.callback'),
            'metadata' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'name' => $data['name'],
                'phone' => $data['phone'],
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
            app(\App\Services\SmsNotificationService::class)->send($enrollment->phone, $message, null, $enrollment->name);
        } catch (\Throwable $e) {
            report($e);
        }

        // Email copy of the credentials
        try {
            \Illuminate\Support\Facades\Mail::send('emails.application-started', [
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
                $this->sendRegistrationCompleteSms($enrollment);
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
            'amount' => (int) round($amount * 100),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('application.tuition.callback'),
            'metadata' => [
                'type' => 'tuition',
                'enrollment_id' => $enrollment->id,
                'course_title' => $course->title,
                'option' => $validated['option'],
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
            $paidNow = (float) (($verify['data']['amount'] ?? 0) / 100);
            $newTotal = round($enrollment->tuition_amount + $paidNow, 2);
            $full = $enrollment->course?->tuition_full ?? 0;
            $status = ($newTotal + 0.01) >= $full ? 'paid' : 'partial';
            $firstCompletion = $enrollment->completed_at === null;

            $enrollment->update([
                'tuition_amount' => $newTotal,
                'tuition_status' => $status,
                'tuition_paid_at' => now(),
                'completed_at' => $enrollment->completed_at ?? now(),
            ]);

            if ($firstCompletion) {
                $this->sendRegistrationCompleteSms($enrollment->fresh('course'));
            }
        } else {
            $enrollment->update(['tuition_status' => $enrollment->tuition_amount > 0 ? 'partial' : 'unpaid']);
        }

        return redirect()->route('application.complete')->with(
            $success ? 'status' : 'enroll_error',
            $success ? 'Payment received. Thank you!' : 'We could not confirm your tuition payment. Please try again.'
        );
    }

    protected function sendRegistrationCompleteSms(CourseEnrollment $enrollment): void
    {
        $course = $enrollment->course?->title ?? 'course';

        $message = "Dear {$enrollment->first_name}, your registration for {$course} has been completed successfully. Further communication regarding the next steps will be sent to you. Thank you for choosing Applyd Academy.";

        try {
            app(\App\Services\SmsNotificationService::class)->send($enrollment->phone, $message, null, $enrollment->name);
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
