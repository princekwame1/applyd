@extends('layouts.admin')

@section('title', 'Digital Products — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Digital Products</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#productCreateTpl" data-modal-title="Add Product">Add Product</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.product-orders') }}">Orders</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.products.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:660px;">
    Templates, guides and workbooks sold on the public
    <a href="{{ route('shop') }}" target="_blank" rel="noopener">Shop</a> page. Uploaded files are private —
    a buyer only reaches one through the order that paid for it. Drag rows to change the order they appear in.
</p>

<div class="card">
    <livewire:digital-products-table />
</div>

@include('partials.quill')

<template id="productCreateTpl">
    @include('dashboard.products.partials.form', ['model' => null])
</template>
@endsection
