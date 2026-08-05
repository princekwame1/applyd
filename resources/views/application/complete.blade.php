@extends('layouts.app')

@section('title', 'Complete Application — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="app-hero-bar">
            <div>
                <span class="page-eyebrow">Applicant Portal</span>
                <h1 class="section-title" style="margin-bottom:6px;">Hi {{ $enrollment->first_name }} 👋</h1>
                <p class="section-lead" style="margin:0;">{{ $enrollment->course?->title ?? 'Course application' }}</p>
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
        <div class="app-grid">
            <aside class="card app-summary">
                <h3>Application details</h3>
                <table class="enroll-summary">
                    <tr><th>Serial No</th><td>{{ $enrollment->serial_no }}</td></tr>
                    <tr><th>Course</th><td>{{ $enrollment->course?->title ?? '—' }}</td></tr>
                    <tr><th>Form fee</th><td>{{ $enrollment->amount_label }} <span class="badge badge-yes">Paid</span></td></tr>
                    <tr><th>Application</th><td>
                        @if ($enrollment->is_completed)
                            <span class="badge badge-yes">Completed</span>
                        @else
                            <span class="badge badge-no">In progress</span>
                        @endif
                    </td></tr>
                </table>
            </aside>

            <div class="card app-form-card">
                @if (session('status'))
                    <div class="success-box" style="margin-bottom:18px;">{{ session('status') }}</div>
                @endif

                @if ($enrollment->is_completed)
                    <div class="enroll-result is-success" style="margin-bottom:8px;">
                        <span class="enroll-result-ic"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <h2 class="section-title" style="font-size:1.4rem; text-align:center;">Application submitted</h2>
                    <p class="section-lead center" style="margin:0 auto;">Thank you, {{ $enrollment->first_name }}. Our team will review your application and be in touch. You can update your details below if needed.</p>
                @else
                    <h2 class="section-title" style="font-size:1.4rem;">Complete your application</h2>
                    <p class="section-lead">Fill in the details below to finish your registration.</p>
                @endif

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
                            @error('goals') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand" style="margin-top:18px;">{{ $enrollment->is_completed ? 'Update application' : 'Submit application' }}</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
