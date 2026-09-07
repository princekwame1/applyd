@extends('layouts.admin')

@section('title', 'Job Postings — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">Job Postings</h1>
        <p style="color:var(--ink-soft);">Every advert employers have submitted, and whether it is on the public board.</p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.job-postings.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($pending) }}</div><div class="lbl">Waiting on you</div></div>
    <div class="stat-card"><div class="num">{{ number_format($approved) }}</div><div class="lbl">Approved</div></div>
    <div class="stat-card"><div class="num">{{ number_format($rejected) }}</div><div class="lbl">Rejected</div></div>
    <div class="stat-card"><div class="num">{{ number_format($heldByCompany) }}</div><div class="lbl">Approved but held</div></div>
</div>

@if ($heldByCompany > 0)
    <div class="mailq" style="margin-bottom:18px;">
        <div class="mailq-warn">
            {{ number_format($heldByCompany) }} approved {{ Str::plural('posting', $heldByCompany) }}
            {{ $heldByCompany === 1 ? 'is' : 'are' }} still off the board because the employer behind
            {{ $heldByCompany === 1 ? 'it' : 'them' }} has not been verified.
            <a href="{{ route('dashboard.companies') }}">Clear the company queue</a> and they go live on their own.
        </div>
    </div>
@endif

<livewire:job-postings-table />
@endsection
