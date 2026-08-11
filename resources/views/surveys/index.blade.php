@extends('layouts.app')

@section('title', 'Pulse Check — Applyd Academy')

@section('content')
<section class="pulse-wrap">
    <div class="container pulse-narrow">
        <span class="pulse-eyebrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1a10 10 0 0 1 0-14.2"/><path d="M7.8 16.2a6 6 0 0 1 0-8.4"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8a6 6 0 0 1 0 8.4"/><path d="M19.1 4.9a10 10 0 0 1 0 14.2"/></svg>
            Digital Tools Bootcamp · Pulse Check
        </span>

        <h1 class="pulse-title">Two quick check-ins.<br>Under a minute each.</h1>
        <p class="pulse-lead">
            Tap into the one that applies to you right now. Your answers help us run a better session today.
        </p>

        @if (empty($surveys))
            <div class="card pulse-empty">
                <p>The check-ins aren't open yet. Please come back when your facilitator shares the link.</p>
            </div>
        @else
            <div class="pulse-cards">
                @foreach ($surveys as $type => $survey)
                    <a class="pulse-card" href="{{ route('surveys.show', $type) }}">
                        <span class="pulse-card-eyebrow">{{ $survey['eyebrow'] }}</span>
                        <h2 class="pulse-card-title">{{ $survey['label'] }}</h2>
                        <p class="pulse-card-desc">
                            {{ $survey['blurb'] }} — {{ $survey['count'] }} short {{ Str::plural('question', $survey['count']) }}.
                        </p>
                        <span class="pulse-card-cta">
                            Start
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
