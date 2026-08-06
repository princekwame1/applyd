@extends('layouts.app')

@section('title', 'Courses at Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">{{ cms('courses', 'hero_eyebrow') }}</span>
        <h1 class="section-title">{{ cms('courses', 'hero_title') }}</h1>
        <p class="section-lead">{{ cms('courses', 'hero_sub') }}</p>
    </div>
</section>

<section id="courses">
    <div class="container">
        <!-- Filters and Search -->
        <div class="courses-filter-bar">
            <div class="filter-item">
                <label>Filter by Category:</label>
                <select id="courseLevel" class="filter-select">
                    <option value="">All Categories</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="All levels">All levels</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Course Name:</label>
                <input type="text" id="courseSearch" class="search-input" placeholder="Search for a course...">
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="courses-grid">
            @forelse ($courses as $course)
                <a class="card course-card" href="{{ route('courses.show', $course) }}" data-level="{{ $course->level }}" data-title="{{ strtolower($course->title) }}">
                    <div class="course-thumb">
                        @if ($course->image_url)
                            <img src="{{ $course->image_url }}" alt="{{ $course->title }}" loading="lazy">
                        @else
                            <div class="course-thumb-placeholder"></div>
                        @endif
                        @if ($course->level)<span class="course-badge">{{ $course->level }}</span>@endif
                    </div>
                    <div class="course-body">
                        <h3>{{ $course->title }}</h3>
                        <p class="course-description">{{ Str::limit(strip_tags($course->description), 110) }}</p>
                        <div class="course-meta">
                            @if ($course->duration)
                                <span class="course-meta-item"><i class="fa-regular fa-clock"></i> {{ $course->duration }}</span>
                            @endif
                            <span class="course-price">{{ $course->price_label }}</span>
                        </div>
                        <span class="btn btn-brand btn-sm course-cta">View Course</span>
                    </div>
                </a>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px 24px;">
                    <h3 style="margin-bottom: 8px;">No courses found</h3>
                    <p style="color: var(--ink-soft);">Check back soon for new courses.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const levelFilter = document.getElementById('courseLevel');
        const searchInput = document.getElementById('courseSearch');
        const courseCards = document.querySelectorAll('.course-card');

        function filterCourses() {
            const selectedLevel = levelFilter.value.toLowerCase();
            const searchTerm = searchInput.value.toLowerCase();

            courseCards.forEach(card => {
                const level = card.dataset.level.toLowerCase();
                const title = card.dataset.title;

                const matchesLevel = !selectedLevel || level === selectedLevel;
                const matchesSearch = !searchTerm || title.includes(searchTerm);

                card.style.display = matchesLevel && matchesSearch ? '' : 'none';
            });
        }

        levelFilter.addEventListener('change', filterCourses);
        searchInput.addEventListener('input', filterCourses);
    });
</script>
@endpush
