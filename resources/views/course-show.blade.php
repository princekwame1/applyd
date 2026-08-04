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

                <div class="course-detail-body">
                    @forelse (preg_split('/\r\n|\r|\n/', trim($course->description ?? '')) as $para)
                        @if (trim($para) !== '')<p>{{ $para }}</p>@endif
                    @empty
                        <p style="color: var(--ink-soft);">More details about this course are coming soon.</p>
                    @endforelse
                </div>
            </div>

            <aside class="course-detail-side">
                <div class="card course-enroll-card">
                    <div class="course-enroll-price">{{ $course->price_label }}</div>
                    @if ($course->duration)<div class="course-enroll-row"><i class="fa-regular fa-clock"></i> {{ $course->duration }}</div>@endif
                    @if ($course->level)<div class="course-enroll-row"><i class="fa-solid fa-signal"></i> {{ $course->level }}</div>@endif
                    <a href="{{ route('contact') }}" class="btn btn-brand" style="width:100%; margin-top:14px;">Enrol / Enquire</a>
                    <a href="{{ route('landing') }}#register" class="btn btn-outline btn-sm" style="width:100%; margin-top:10px;">Join the free bootcamp</a>
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
