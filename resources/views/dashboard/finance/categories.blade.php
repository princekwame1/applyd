@extends('layouts.admin')

@section('title', 'Finance categories — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Finance Categories</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#categoryCreateTpl" data-modal-title="New category">New Category</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.finance') }}">Back to Finance</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:720px;">
    The headings entries get filed under. Each one sits on a single side of the books, so an expense
    heading can never be picked for income. Drag rows to set the order they're offered in.
</p>

<div class="card">
    <livewire:finance-categories-table />
</div>

<template id="categoryCreateTpl">
    @include('dashboard.finance.partials.category-form', ['model' => null])
</template>
@endsection
