@extends('layouts.app')

@section('title', 'Complete Registration — Applyd Academy')

@php
    use App\Models\Course;
    $course = $enrollment->course;
    $requiresTuition = $course && $course->requiresTuition();
    $hasDetails = $enrollment->hasDetails();
    $completed = $enrollment->is_completed;
    $full = $course?->tuition_full ?? 0;
    $half = $course?->tuition_half ?? 0;
    $balance = $enrollment->tuitionBalance();
    $step = ! $hasDetails ? 1 : (($requiresTuition && ! $completed) ? 2 : 3);
@endphp

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="app-hero-bar">
            <div>
                <span class="page-eyebrow">Applicant Portal</span>
                <h1 class="section-title" style="margin-bottom:6px;">Hi {{ $enrollment->first_name }} 👋</h1>
                <p class="section-lead" style="margin:0;">{{ $course?->title ?? 'Course application' }}</p>
            </div>
            <form method="POST" action="{{ route('application.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Sign out</button>
            </form>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <ol class="app-steps">
            <li class="{{ $step > 1 ? 'done' : ($step === 1 ? 'active' : '') }}"><span>1</span> Application details</li>
            <li class="{{ $step > 2 ? 'done' : ($step === 2 ? 'active' : '') }}"><span>2</span> Tuition payment</li>
            <li class="{{ $completed ? 'done' : '' }}"><span>3</span> Complete</li>
        </ol>

        @if (session('status'))
            <div class="success-box" style="margin-bottom:18px;">{{ session('status') }}</div>
        @endif
        @if (session('enroll_error'))
            <div class="error-box" style="margin-bottom:18px;">{{ session('enroll_error') }}</div>
        @endif

        <div class="app-grid">
            <aside class="card app-summary">
                <h3>Registration summary</h3>
                <table class="enroll-summary">
                    <tr><th>Serial No</th><td>{{ $enrollment->serial_no }}</td></tr>
                    <tr><th>Course</th><td>{{ $course?->title ?? '—' }}</td></tr>
                    <tr><th>Form fee</th><td>{{ $enrollment->amount_label }} <span class="badge badge-yes">Paid</span></td></tr>
                    @if ($requiresTuition)
                        <tr><th>Tuition</th><td>{{ Course::money($full) }}</td></tr>
                        <tr><th>Tuition status</th><td>
                            <span class="badge {{ $enrollment->tuition_status === 'paid' ? 'badge-yes' : 'badge-no' }}">{{ $enrollment->tuition_status_label }}</span>
                        </td></tr>
                        @if ($enrollment->tuition_paid > 0)
                            <tr><th>Paid</th><td>{{ Course::money($enrollment->tuition_paid) }}</td></tr>
                        @endif
                        @if ($balance > 0)
                            <tr><th>Balance</th><td>{{ Course::money($balance) }}</td></tr>
                        @endif
                    @endif
                    <tr><th>Registration</th><td>
                        <span class="badge {{ $completed ? 'badge-yes' : 'badge-no' }}">{{ $completed ? 'Complete' : 'In progress' }}</span>
                    </td></tr>
                </table>
            </aside>

            <div class="card app-form-card">
                {{-- STEP 1: Application details --}}
                @if ($step === 1)
                    <h2 class="section-title" style="font-size:1.4rem;">Complete your application</h2>
                    <p class="section-lead">Fill in your details to continue to payment.</p>

                    @if ($errors->any())
                        <div class="error-box" style="margin:14px 0;">Please fix the highlighted fields below.</div>
                    @endif

                    <form method="POST" action="{{ route('application.submit') }}" class="app-form">
                        @csrf
                        <div class="form-grid">
                            <div>
                                <label class="field-label" for="date_of_birth">Date of Birth <span class="req">*</span></label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($enrollment->date_of_birth)->format('Y-m-d')) }}" required>
                                @error('date_of_birth') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="gender">Gender <span class="req">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select…</option>
                                    @foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $g)
                                        <option value="{{ $g }}" @selected(old('gender', $enrollment->gender) === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                                @error('gender') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="country">Country <span class="req">*</span></label>
                                <input type="text" id="country" name="country" value="{{ old('country', $enrollment->country) }}" required>
                                @error('country') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="city">City / Town <span class="req">*</span></label>
                                <input type="text" id="city" name="city" value="{{ old('city', $enrollment->city) }}" required>
                                @error('city') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="full">
                                <label class="field-label" for="education_level">Highest Education Level <span class="req">*</span></label>
                                <select id="education_level" name="education_level" required>
                                    <option value="">Select…</option>
                                    @foreach (['SHS / High School', 'Diploma / HND', 'Bachelor\'s Degree', 'Master\'s Degree', 'Other'] as $ed)
                                        <option value="{{ $ed }}" @selected(old('education_level', $enrollment->education_level) === $ed)>{{ $ed }}</option>
                                    @endforeach
                                </select>
                                @error('education_level') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="full">
                                <label class="field-label" for="goals">What do you hope to achieve? <small>(optional)</small></label>
                                <textarea id="goals" name="goals" rows="4" style="width:100%; padding:10px 12px; border:1.5px solid #d8d2d2; border-radius:8px; font-family:inherit;">{{ old('goals', $enrollment->goals) }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-brand" style="margin-top:18px;">Save &amp; continue →</button>
                    </form>

                {{-- STEP 2: Tuition payment --}}
                @elseif ($step === 2)
                    <h2 class="section-title" style="font-size:1.4rem;">Pay your tuition to complete registration</h2>
                    <p class="section-lead">Choose to pay in full or make a 50% part payment now. Your registration is confirmed once payment is received.</p>

                    <div class="pay-options">
                        <form method="POST" action="{{ route('application.tuition') }}" class="pay-option">
                            @csrf
                            <input type="hidden" name="option" value="full">
                            <span class="pay-option-tag">Pay in full</span>
                            <span class="pay-option-amt">{{ Course::money($full) }}</span>
                            <span class="pay-option-note">Settle your tuition in one payment.</span>
                            <button type="submit" class="btn btn-brand" style="width:100%;">Pay {{ Course::money($full) }}</button>
                        </form>

                        <form method="POST" action="{{ route('application.tuition') }}" class="pay-option pay-option--alt">
                            @csrf
                            <input type="hidden" name="option" value="half">
                            <span class="pay-option-tag">Part payment (50%)</span>
                            <span class="pay-option-amt">{{ Course::money($half) }}</span>
                            <span class="pay-option-note">Pay half now, the balance of {{ Course::money($full - $half) }} later.</span>
                            <button type="submit" class="btn btn-outline" style="width:100%;">Pay {{ Course::money($half) }}</button>
                        </form>
                    </div>
                    <p class="enroll-secure" style="margin-top:18px;"><i class="fa-solid fa-lock"></i> Secure payment via Paystack</p>

                {{-- STEP 3: Done --}}
                @else
                    <div class="enroll-result is-success" style="margin-bottom:8px;">
                        <span class="enroll-result-ic"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <h2 class="section-title" style="font-size:1.5rem; text-align:center;">Registration complete 🎉</h2>
                    <p class="section-lead center" style="margin:0 auto;">Thank you, {{ $enrollment->first_name }}. Your registration for {{ $course?->title }} is confirmed. Further communication about the next steps will be sent to you.</p>

                    @if ($requiresTuition && $balance > 0)
                        <div class="pay-balance">
                            <div>
                                <strong>Outstanding balance: {{ Course::money($balance) }}</strong>
                                <p>You made a 50% part payment. You can clear the remaining balance now.</p>
                            </div>
                            <form method="POST" action="{{ route('application.tuition') }}">
                                @csrf
                                <input type="hidden" name="option" value="balance">
                                <button type="submit" class="btn btn-brand btn-sm">Pay balance {{ Course::money($balance) }}</button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
