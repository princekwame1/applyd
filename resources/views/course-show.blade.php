@extends('layouts.app')

@section('title', $course->title.' — Applyd Academy')

@section('content')
<section class="course-detail">
    <div class="container">
        <a href="{{ route('courses') }}" class="course-back"><i class="fa-solid fa-arrow-left"></i> All courses</a>

        <div class="course-detail-grid">
            <div class="course-detail-main">
                @if ($course->level)<span class="course-badge course-badge-static">{{ $course->level }}</span>@endif
                <h1 class="course-detail-title">{{ $course->title }}</h1>

                <div class="course-detail-meta">
                    @if ($course->duration)
                        <span class="course-meta-item"><i class="fa-regular fa-clock"></i> {{ $course->duration }}</span>
                    @endif
                    @if ($course->level)
                        <span class="course-meta-item"><i class="fa-solid fa-signal"></i> {{ $course->level }}</span>
                    @endif
                    <span class="course-meta-item"><i class="fa-solid fa-tag"></i> {{ $course->price_label }}</span>
                </div>

                @if ($course->image_url)
                    <img src="{{ $course->image_url }}" alt="{{ $course->title }}" class="course-detail-image">
                @endif

                <div class="course-detail-body job-description">
                    @if (trim($course->description ?? '') === '')
                        <p style="color: var(--ink-soft);">More details about this course are coming soon.</p>
                    @elseif (str_contains($course->description, '<'))
                        {!! $course->description !!}
                    @else
                        @foreach (preg_split('/\r\n|\r|\n/', trim($course->description)) as $para)
                            @if (trim($para) !== '')<p>{{ $para }}</p>@endif
                        @endforeach
                    @endif
                </div>
            </div>

            <aside class="course-detail-side">
                <div class="card course-enroll-card">
                    @php $attendance = $course->attendanceOptions(); @endphp
                    @if (count($attendance))
                        <div class="course-enroll-label">Tuition by attendance</div>
                        <div class="attend-pricing">
                            @foreach ($attendance as $opt)
                                <div class="attend-pricing-row">
                                    <span>{{ $opt['label'] }}</span>
                                    <strong>{{ \App\Models\Course::money($opt['price']) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="course-enroll-label">Course fee</div>
                        <div class="course-enroll-price">{{ $course->price_label }}</div>
                    @endif
                    @if ($course->duration)<div class="course-enroll-row"><i class="fa-regular fa-clock"></i> {{ $course->duration }}</div>@endif
                    @if ($course->level)<div class="course-enroll-row"><i class="fa-solid fa-signal"></i> {{ $course->level }}</div>@endif

                    <div class="enroll-form-box">
                        <div class="enroll-fee-row">
                            <span>Registration form</span>
                            <strong>{{ $course->form_fee_label }}</strong>
                        </div>
                        <p class="enroll-note">Purchase the registration form to secure your place. We'll follow up with the next steps.</p>

                        @if (session('enroll_error'))
                            <div class="error-box" style="margin-bottom:12px;">{{ session('enroll_error') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="error-box" style="margin-bottom:12px;">Please check the details below.</div>
                        @endif

                        <form method="POST" action="{{ route('courses.enroll.store', $course) }}" class="enroll-form">
                            @csrf
                            <div>
                                <label class="field-label" for="en_name">Full Name <span class="req">*</span></label>
                                <input type="text" id="en_name" name="name" value="{{ old('name') }}" required>
                                @error('name') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="en_email">Email <span class="req">*</span></label>
                                <input type="email" id="en_email" name="email" value="{{ old('email') }}" required>
                                @error('email') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="en_phone">Phone <span class="req">*</span></label>
                                <input type="tel" id="en_phone" name="phone" value="{{ old('phone') }}" required>
                                @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-brand" style="width:100%; margin-top:4px;">Pay {{ $course->form_fee_label }} &amp; Register</button>
                            <p class="enroll-secure"><i class="fa-solid fa-lock"></i> Secure payment via Paystack</p>
                        </form>
                    </div>
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <div class="course-related">
                <h2 class="section-title" style="margin-bottom: 20px;">Related courses</h2>
                <div class="courses-grid">
                    @foreach ($related as $rel)
                        <a class="card course-card" href="{{ route('courses.show', $rel) }}">
                            <div class="course-thumb">
                                @if ($rel->image_url)
                                    <img src="{{ $rel->image_url }}" alt="{{ $rel->title }}" loading="lazy">
                                @else
                                    <div class="course-thumb-placeholder"></div>
                                @endif
                                @if ($rel->level)<span class="course-badge">{{ $rel->level }}</span>@endif
                            </div>
                            <div class="course-body">
                                <h3>{{ $rel->title }}</h3>
                                <p class="course-description">{{ Str::limit(strip_tags($rel->description), 90) }}</p>
                                <div class="course-meta">
                                    @if ($rel->duration)<span class="course-meta-item"><i class="fa-regular fa-clock"></i> {{ $rel->duration }}</span>@endif
                                    <span class="course-price">{{ $rel->price_label }}</span>
                                </div>
                                <span class="btn btn-brand btn-sm course-cta">View Course</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
