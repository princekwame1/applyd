@extends('layouts.admin')

@section('title', 'Companies — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">Companies</h1>
        <p style="color:var(--ink-soft);">Every employer on the job board, and whether we have confirmed who they are.</p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.companies.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($pending) }}</div><div class="lbl">Waiting on you</div></div>
    <div class="stat-card"><div class="num">{{ number_format($approved) }}</div><div class="lbl">Verified</div></div>
    <div class="stat-card"><div class="num">{{ number_format($rejected) }}</div><div class="lbl">Rejected</div></div>
</div>

{{-- <p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:680px;">
    We check the Ghana Card of every job poster before their adverts reach the public board. They can use their
    portal and write postings while they wait — verification gates what the public sees, not their account.
    Open a company to see the card, then verify or reject with a reason. Either way they get an email.
</p> --}}

<livewire:companies-table />
@endsection
