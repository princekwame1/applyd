@extends('layouts.admin')

@section('title', 'Email Delivery')

@section('content')
<div class="page-head">
    <div>
        <h2 class="section-title">Email Delivery</h2>
        <p style="color: var(--ink-soft);">Every email the site has sent, with its delivery status. Resend any message that failed or that a registrant never received.</p>
    </div>
    <a class="btn btn-brand" href="{{ route('dashboard.email-logs.export') }}">Export Excel</a>
</div>

@if (session('success'))
    <div class="success-box">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card">
    <livewire:email-logs-table />
</div>
@endsection
