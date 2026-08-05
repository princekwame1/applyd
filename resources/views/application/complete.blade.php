@extends('layouts.app')

@section('title', 'Complete Registration — Applyd Academy')

@php
    use App\Models\Course;
    $course = $enrollment->course;
    $options = $course ? $course->attendanceOptions() : [];
    $requiresTuition = $course && $course->requiresTuition();
    $hasDetails = $enrollment->hasDetails();
    $completed = $enrollment->is_completed;
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
                        @if ($enrollment->attendance_type)
                            <tr><th>Attendance</th><td>{{ $enrollment->attendance_label }}</td></tr>
                            <tr><th>Tuition</th><td>{{ Course::money($enrollment->tuitionFull()) }}</td></tr>
                        @else
                            <tr><th>Tuition</th><td>from {{ Course::money($course->tuition_full) }}</td></tr>
                        @endif
                        <tr><th>Tuition status</th><td>
                            <span class="badge {{ $enrollment->tuition_status === 'paid' ? 'badge-yes' : 'badge-no' }}">{{ $enrollment->tuition_status_label }}</span>
                        </td></tr>
                        @if ($enrollment->tuition_paid > 0)
                            <tr><th>Paid</th><td>{{ Course::money($enrollment->tuition_paid) }}</td></tr>
                        @endif
                        @if ($balance > 0 && $enrollment->attendance_type)
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

                {{-- STEP 2: Attendance + tuition payment --}}
                @elseif ($step === 2)
                    <h2 class="section-title" style="font-size:1.4rem;">Choose attendance &amp; pay tuition</h2>
                    <p class="section-lead">Select how you'll attend, then pay in full or make a 50% part payment. Registration confirms once payment is received.</p>

                    <form method="POST" action="{{ route('application.tuition') }}" class="tuition-form" id="tuitionForm">
                        @csrf
                        <div class="attend-choose">
                            <span class="tuition-label">Attendance type</span>
                            <div class="attend-radios">
                                @foreach ($options as $i => $opt)
                                    <label class="attend-radio">
                                        <input type="radio" name="attendance_type" value="{{ $opt['key'] }}" data-price="{{ $opt['price'] }}" {{ $i === 0 ? 'checked' : '' }} required>
                                        <span class="attend-radio-body">
                                            <strong>{{ $opt['label'] }}</strong>
                                            <span class="attend-price">{{ Course::money($opt['price']) }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="attend-choose">
                            <span class="tuition-label">Payment</span>
                            <div class="attend-radios">
                                <label class="attend-radio">
                                    <input type="radio" name="option" value="full" data-factor="1" checked>
                                    <span class="attend-radio-body"><strong>Pay in full</strong><span class="attend-price" data-amt="full">—</span></span>
                                </label>
                                <label class="attend-radio">
                                    <input type="radio" name="option" value="half" data-factor="0.5">
                                    <span class="attend-radio-body"><strong>Part payment (50%)</strong><span class="attend-price" data-amt="half">—</span></span>
                                </label>
                            </div>
                        </div>

                        <div class="tuition-total">
                            <span>You'll pay now</span>
                            <strong id="tuitionTotal">—</strong>
                        </div>
                        <button type="submit" class="btn btn-brand" style="width:100%; margin-top:6px;">Proceed to secure payment</button>
                        <p class="enroll-secure" style="margin-top:12px;"><i class="fa-solid fa-lock"></i> Secure payment via Paystack</p>
                    </form>

                    <script>
                    (function () {
                        var form = document.getElementById('tuitionForm');
                        if (!form) return;
                        function money(n) { return 'GHS ' + Number(n).toFixed(2).replace(/\.00$/, ''); }
                        function price() { var r = form.querySelector('input[name=attendance_type]:checked'); return r ? parseFloat(r.dataset.price) : 0; }
                        function factor() { var r = form.querySelector('input[name=option]:checked'); return r ? parseFloat(r.dataset.factor) : 1; }
                        function update() {
                            var p = price();
                            var f = form.querySelector('[data-amt=full]'); if (f) f.textContent = money(p);
                            var h = form.querySelector('[data-amt=half]'); if (h) h.textContent = money(p * 0.5);
                            document.getElementById('tuitionTotal').textContent = money(p * factor());
                        }
                        form.addEventListener('change', update);
                        update();
                    })();
                    </script>

                {{-- STEP 3: Done --}}
                @else
                    <div class="enroll-result is-success" style="margin-bottom:8px;">
                        <span class="enroll-result-ic"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <h2 class="section-title" style="font-size:1.5rem; text-align:center;">Registration complete 🎉</h2>
                    <p class="section-lead center" style="margin:0 auto;">Thank you, {{ $enrollment->first_name }}. Your registration for {{ $course?->title }}{{ $enrollment->attendance_type ? ' ('.$enrollment->attendance_label.')' : '' }} is confirmed. Further communication about the next steps will be sent to you.</p>

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
