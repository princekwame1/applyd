@extends('layouts.admin')

@section('title', 'Course Registrations — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Course Registrations</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.course-registrations.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card">
        <div class="num">{{ number_format($stats['total']) }}</div>
        <div class="lbl">Total Registrations</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ number_format($stats['completed']) }}</div>
        <div class="lbl">Completed</div>
    </div>
    <div class="stat-card">
        <div class="num">GHS {{ number_format((float) $stats['form_revenue'], 2) }}</div>
        <div class="lbl">Form-fee Revenue</div>
    </div>
    <div class="stat-card">
        <div class="num">GHS {{ number_format((float) $stats['tuition_revenue'], 2) }}</div>
        <div class="lbl">Tuition Collected</div>
    </div>
</div>

<div class="card">
    <livewire:course-enrollments-table />
</div>
@endsection
