@extends('layouts.app')

@section('title', 'Jobs — Applyd Academy')

@section('content')
<section class="alt contact-hero">
    <div class="container center">
        <h1 class="section-title">Jobs &amp; Opportunities</h1>
        <p class="section-lead">Openings from companies in our network. Apply with your CV — no account needed.</p>
        <a href="{{ route('companies.register') }}" class="btn btn-brand btn-sm">Are you hiring? Post a job</a>
    </div>
</section>

<section>
    <div class="container">
        @if ($openings->isEmpty())
            <div class="card center" style="padding: 48px;">
                <h3 style="margin-bottom: 8px;">No open positions right now</h3>
                <p style="color: var(--ink-soft);">Check back soon — or get job-ready in the meantime with our free bootcamp.</p>
                <a href="{{ route('landing') }}#register" class="btn btn-brand btn-sm" style="margin-top: 16px;">Join the Free Bootcamp</a>
            </div>
        @else
            <div class="job-list">
                @foreach ($openings as $opening)
                    <a class="card job-card" href="{{ route('jobs.show', $opening) }}">
                        <div class="job-card-main">
                            <h3>{{ $opening->title }}</h3>
                            <p class="job-company">{{ $opening->company->name }}{{ $opening->location ? ' · '.$opening->location : '' }}</p>
                            <p class="job-excerpt">{{ Str::limit(strip_tags($opening->description), 150) }}</p>
                        </div>
                        <div class="job-card-side">
                            <span class="tag">{{ $opening->type }}</span>
                            @if ($opening->salary_range)
                                <span class="job-salary">{{ $opening->salary_range }}</span>
                            @endif
                            @if ($opening->deadline)
                                <span class="job-deadline">Apply by {{ $opening->deadline->format('M j, Y') }}</span>
                            @endif
                            <span class="tool-link" style="margin-top:auto;">View &amp; apply →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
