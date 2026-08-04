@extends('layouts.admin')

@section('title', 'Courses — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Courses</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#courseCreateTpl" data-modal-title="Add Course">Add Course</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.courses.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card">
    <livewire:courses-table />
</div>

<template id="courseCreateTpl">
    @include('dashboard.courses.partials.form', ['model' => null])
</template>
@endsection
