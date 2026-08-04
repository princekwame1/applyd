@extends('layouts.admin')

@section('title', 'Tools — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Tools</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#toolCreateTpl" data-modal-title="Add Tool">Add Tool</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.tools.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card">
    <livewire:tools-table />
</div>

<template id="toolCreateTpl">
    @include('dashboard.tools.partials.form', ['model' => null])
</template>
@endsection
