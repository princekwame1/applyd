@extends('layouts.admin')

@section('title', 'Recruiter Plans — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Recruiter Plans</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#planCreateTpl" data-modal-title="Add Plan">Add Plan</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.plan-purchases') }}">Purchases</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.recruiter-plans.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:660px;">
    What recruiters buy to open CVs from the talent pool. One credit opens one candidate, permanently.
    Credits never expire — a recruiter “expands their plan” by buying again, which simply adds more.
    Drag rows to set the order on the company pricing page.
</p>

<div class="card">
    <livewire:recruiter-plans-table />
</div>

<template id="planCreateTpl">
    @include('dashboard.recruiter-plans.partials.form', ['model' => null])
</template>
@endsection
