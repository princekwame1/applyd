<?php

namespace App\Http\Controllers;

use App\Exports\CourseEnrollmentsExport;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Support\Paystack;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CourseEnrollmentController extends Controller
{
    public function adminIndex()
    {
        return view('dashboard.course-registrations', [
            'stats' => [
                'total' => CourseEnrollment::count(),
                'paid' => CourseEnrollment::where('status', 'paid')->count(),
                'revenue' => CourseEnrollment::where('status', 'paid')->sum('amount'),
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
            app(\App\Services\SmsNotificationService::class)->send($enrollment->phone, $message);
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

        $data['completed_at'] = now();
        $enrollment->update($data);

        return redirect()->route('application.complete')->with('status', 'Your application has been submitted. Thank you!');
    }

    protected function currentApplicant(Request $request): CourseEnrollment
    {
        $id = $request->session()->get('applicant_id');
        $enrollment = $id ? CourseEnrollment::with('course')->find($id) : null;

        abort_if(! $enrollment, 403);

        return $enrollment;
    }
}
