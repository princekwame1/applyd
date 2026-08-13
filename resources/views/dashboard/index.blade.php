@extends('layouts.admin')

@section('content')
<div class="page-head">
    <h1 class="section-title">Registrations</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.export') }}">Export Excel</a>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ $stats['total'] }}</div><div class="lbl">Total Registrations</div></div>
    {{-- Hover (or focus, for keyboards) to see which countries and how many from each. --}}
    <div class="stat-card {{ $countryCounts->isNotEmpty() ? 'stat-pop-host' : '' }}"
         @if ($countryCounts->isNotEmpty()) tabindex="0" aria-describedby="countryBreakdown" @endif>
        <div class="num">{{ number_format($stats['countries']) }}</div>
        <div class="lbl">Countries Reached</div>
        @if ($countryCounts->isNotEmpty())
            <div class="stat-meta">Hover for the breakdown</div>
            <div class="stat-pop" id="countryBreakdown" role="tooltip">
                <div class="stat-pop-head">
                    <span>Registrations by country</span>
                    <b>{{ number_format($stats['total']) }}</b>
                </div>
                <ul class="stat-pop-list">
                    @foreach ($countryCounts as $country => $count)
                        <li>
                            <span class="stat-pop-name">{{ $country }}</span>
                            <span class="stat-pop-bar" aria-hidden="true">
                                <span style="width: {{ $stats['total'] ? round($count / $stats['total'] * 100) : 0 }}%;"></span>
                            </span>
                            <b>{{ number_format($count) }}</b>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <div class="stat-card"><div class="num">{{ $stats['opted_in'] }}</div><div class="lbl">Marketing Opt-ins</div></div>
    <div class="stat-card"><div class="num">{{ $stats['today'] }}</div><div class="lbl">Registered Today</div></div>
</div>

@if ($topTools->isNotEmpty())
    <div class="card" style="margin-bottom: 24px;">
        <h3>Most Requested Tools</h3>
        <div class="tool-tags">
            @foreach ($topTools as $tool => $count)
                <span class="tag">{{ $tool }} · {{ $count }}</span>
            @endforeach
        </div>
    </div>
@endif

<div class="card">
    <livewire:registrations-table />
</div>
@endsection
