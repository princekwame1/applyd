@extends('layouts.company')

@section('title', 'Talent Pool — '.$company->name)

@section('content')
<div class="page-head">
    <h1 class="section-title">Talent Pool</h1>
    <a class="btn btn-sm btn-brand" href="{{ route('company.plans') }}">Buy CV credits</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($creditsLeft) }}</div><div class="lbl">Credits left</div></div>
    <div class="stat-card"><div class="num">{{ number_format($unlockedCount) }}</div><div class="lbl">CVs unlocked</div></div>
    <div class="stat-card"><div class="num">{{ number_format($profiles->total()) }}</div><div class="lbl">Candidates matching your jobs</div></div>
</div>

@if (! $sectors)
    <div class="card center" style="padding:40px;">
        <h3 style="margin-bottom:8px;">Post a job to see candidates</h3>
        <p style="color:var(--ink-soft); max-width:520px; margin:0 auto 16px;">
            Candidates in the pool are matched to you by sector. As soon as you publish a job with a sector on it,
            everyone waiting for work in that sector shows up here.
        </p>
        <a class="btn btn-brand btn-sm" href="{{ route('company.home') }}">Post a job</a>
    </div>
@else
    <p style="color:var(--ink-soft); margin:-6px 0 16px;">
        Matched on the {{ Str::plural('sector', count($sectors)) }} you're hiring in:
        <b>{{ implode(', ', $sectors) }}</b>. Names and CVs open with one credit each, and stay open for good.
    </p>

    @if (count($sectors) > 1)
        <div class="blog-tabs" style="margin-bottom:20px;">
            <a href="{{ route('company.talent') }}" class="blog-tab {{ ! $activeSector ? 'active' : '' }}">All</a>
            @foreach ($sectors as $sector)
                <a href="{{ route('company.talent', ['sector' => $sector]) }}"
                   class="blog-tab {{ $activeSector === $sector ? 'active' : '' }}">{{ $sector }}</a>
            @endforeach
        </div>
    @endif

    @if ($profiles->isEmpty())
        <div class="card center" style="padding:40px;">
            <h3 style="margin-bottom:8px;">Nobody here yet</h3>
            <p style="color:var(--ink-soft);">No candidates are waiting in {{ $activeSector ?: 'your sectors' }} right now. Check back — the pool grows every week.</p>
        </div>
    @else
        <div class="talent-grid">
            @foreach ($profiles as $profile)
                @php($unlocked = $profile->unlocks->isNotEmpty())
                <div class="card talent-card {{ $unlocked ? 'is-unlocked' : '' }}">
                    <div class="talent-card-head">
                        <h3>{{ $unlocked ? $profile->full_name : $profile->masked_name }}</h3>
                        @if ($unlocked)
                            <span class="badge badge-yes">Unlocked</span>
                        @else
                            <span class="talent-lock" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            </span>
                        @endif
                    </div>

                    @php($headline = $unlocked ? $profile->headline : $profile->public_headline)
                    @if ($headline)
                        <p class="talent-headline">{{ $headline }}</p>
                    @endif

                    <div class="talent-tags">
                        @foreach ($profile->sectorList() as $sector)
                            <span class="tag">{{ $sector }}</span>
                        @endforeach
                    </div>

                    {{-- Scrubbed BEFORE the trim, so a number cut off at 160 characters
                         cannot survive as a half-visible run. --}}
                    @php($summary = $unlocked ? $profile->summary : $profile->public_summary)
                    @if ($summary)
                        <p class="talent-summary">{{ Str::limit($summary, 160) }}</p>
                    @endif

                    <div class="talent-contact">
                        @if ($unlocked)
                            <div><b>Email</b> <a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a></div>
                            @if ($profile->phone)<div><b>Phone</b> {{ $profile->phone }}</div>@endif
                            @if ($profile->location)<div><b>Based in</b> {{ $profile->location }}</div>@endif
                        @else
                            <div class="talent-masked" aria-hidden="true">●●●●●●●●●●@●●●●●●●.com</div>
                            <div class="talent-masked" aria-hidden="true">+●●● ●● ●●● ●●●●</div>
                            <span class="sr-only">Contact details are hidden until you unlock this CV.</span>
                        @endif
                    </div>

                    <div class="talent-card-foot">
                        @if ($unlocked)
                            <a class="btn btn-sm btn-brand" href="{{ route('company.talent.cv', $profile) }}">Download CV</a>
                            <span class="talent-meta">{{ $profile->cv_name }}</span>
                        @else
                            <form method="POST" action="{{ route('company.talent.unlock', $profile) }}"
                                  data-confirm="Unlock this CV? It costs 1 credit and stays open to you permanently.">
                                @csrf
                                <input type="hidden" name="sector" value="{{ $activeSector }}">
                                <button type="submit" class="btn btn-sm btn-brand" @disabled($creditsLeft < 1)>Unlock CV — 1 credit</button>
                            </form>
                            @if ($creditsLeft < 1)
                                <span class="talent-meta">No credits left — <a href="{{ route('company.plans') }}">buy more</a></span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($profiles->hasPages())
            <div class="jobs-pagination" style="margin-top:32px;">{{ $profiles->links() }}</div>
        @endif
    @endif
@endif
@endsection
