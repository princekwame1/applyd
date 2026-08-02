@extends('layouts.admin')

@section('content')
<div class="page-head">
    <h1 class="section-title">Registrations</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.export') }}">Export Excel</a>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ $stats['total'] }}</div><div class="lbl">Total Registrations</div></div>
    <div class="stat-card"><div class="num">{{ $stats['countries'] }}</div><div class="lbl">Countries Reached</div></div>
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
