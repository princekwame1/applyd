@extends('layouts.app')

@section('title', 'Jobs — Applyd Academy')
@section('og_description', cms('jobs', 'hero_sub') ?? '')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">{{ cms('jobs', 'hero_eyebrow') }}</span>
        <h1 class="section-title">{{ cms('jobs', 'hero_title') }}</h1>
        <p class="section-lead">{{ cms('jobs', 'hero_sub') }}</p>
        <div class="hero-actions" style="margin-top:22px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="{{ route('companies.register') }}" class="btn btn-brand btn-sm">{{ cms('jobs', 'hero_cta') }}</a>
            <a href="{{ route('talent.create') }}" class="btn btn-sm btn-outline">{{ cms('jobs', 'talent_cta') }}</a>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <!-- Filters -->
        <div class="jobs-filter-bar">
            <form method="GET" action="{{ route('jobs') }}" class="jobs-filter-form">
                <div class="filter-item">
                    <label>Filter by Job Type:</label>
                    <select name="type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Job Types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" {{ $selectedType === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Filter by Sector:</label>
                    <select name="sector" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Sectors</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" {{ $selectedSector === $sector ? 'selected' : '' }}>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Job Search:</label>
                    <div class="jobs-search-row">
                        <input type="text" name="search" placeholder="Search jobs..." value="{{ $search }}" class="search-input">
                        <button type="submit" class="btn btn-brand btn-sm">Search</button>
                    </div>
                </div>
            </form>
        </div>

        @if ($search || $selectedType || $selectedSector || $selectedLocation)
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ route('jobs') }}" class="btn btn-outline btn-sm">Clear all filters</a>
            </div>
        @endif

        @if ($openings->isEmpty())
            <div class="card center" style="padding: 48px;">
                <h3 style="margin-bottom: 8px;">No open positions matching your search</h3>
                <p style="color: var(--ink-soft);">Try adjusting your filters or search terms. In the meantime, get job-ready with our free bootcamp.</p>
                <a href="{{ route('landing') }}#register" class="btn btn-brand btn-sm" style="margin-top: 16px;">Join the Free Bootcamp</a>
            </div>
        @else
            <div class="job-list">
                @foreach ($openings as $opening)
                    <a class="card job-card" href="{{ route('jobs.show', $opening) }}">
                        <div class="job-card-header">
                            <div class="job-card-main">
                                <h3 class="job-title">{{ $opening->title }}</h3>
                                <p class="job-company">{{ $opening->company->name }}</p>
                                <div class="job-meta-tags">
                                    @if ($opening->location)
                                        <span class="job-meta-tag">{{ $opening->location }}</span>
                                    @endif
                                    @if ($opening->sector)
                                        <span class="job-meta-tag">{{ $opening->sector }}</span>
                                    @endif
                                    <span class="job-type-badge">{{ $opening->type }}</span>
                                </div>
                            </div>
                            @if ($opening->salary_range)
                                <div class="job-card-side">
                                    <span class="job-salary">{{ $opening->salary_range }}</span>
                                </div>
                            @endif
                        </div>
                        <p class="job-excerpt">{{ Str::limit(strip_tags($opening->description), 150) }}</p>
                        <div class="job-card-footer">
                            @if ($opening->deadline)
                                <span class="job-deadline">Apply by {{ $opening->deadline->format('M j') }}</span>
                            @else
                                <span class="job-deadline">No deadline</span>
                            @endif
                            <span class="job-cta">View &amp; apply →</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if ($openings->hasPages())
                <div class="jobs-pagination" style="margin-top: 48px;">
                    {{ $openings->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
