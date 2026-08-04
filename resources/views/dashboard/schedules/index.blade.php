@extends('layouts.admin')

@section('title', 'Schedules — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">24-Day Journey Schedule</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#scheduleCreateTpl" data-modal-title="Add Schedule Entry">Add Entry</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.schedules.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card">
    <livewire:schedules-table />
</div>

<template id="scheduleCreateTpl">
    @include('dashboard.schedules.partials.form', ['model' => null])
</template>
@endsection
