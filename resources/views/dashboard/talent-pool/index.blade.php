@extends('layouts.admin')

@section('title', 'Talent Pool — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Talent Pool</h1>
    <div style="display:flex; gap:10px;">
        <a class="btn btn-sm btn-outline" href="{{ route('talent.create') }}" target="_blank" rel="noopener">Public form</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.talent-pool.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($total) }}</div><div class="lbl">CVs in the pool</div></div>
    <div class="stat-card"><div class="num">{{ number_format($available) }}</div><div class="lbl">Open to work</div></div>
    <div class="stat-card"><div class="num">{{ number_format($unlocks) }}</div><div class="lbl">Credits spent by recruiters</div></div>
</div>

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:660px;">
    CVs dropped at <a href="{{ route('talent.create') }}" target="_blank" rel="noopener">/talent-pool</a> without applying to a
    specific job. Recruiters see a candidate once they publish a job in a matching sector, and spend a credit to open the CV.
</p>

<div class="card">
    <livewire:talent-profiles-table />
</div>
@endsection
