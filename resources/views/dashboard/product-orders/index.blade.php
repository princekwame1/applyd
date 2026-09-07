@extends('layouts.admin')

@section('title', 'Product Orders — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Product Orders</h1>
    <div style="display:flex; gap:10px;">
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.products') }}">Products</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.product-orders.export') }}">Export Excel</a>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="num">{{ config('services.paystack.currency', 'GHS') }} {{ number_format($revenue, 2) }}</div>
        <div class="lbl">Paid revenue</div>
    </div>
    <div class="stat-card"><div class="num">{{ number_format($salesCount) }}</div><div class="lbl">Settled orders</div></div>
    <div class="stat-card"><div class="num">{{ number_format($pendingCount) }}</div><div class="lbl">Started, not paid</div></div>
    <div class="stat-card"><div class="num">{{ number_format($downloads) }}</div><div class="lbl">Downloads</div></div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-4px 0 18px; max-width:660px;">
    Every checkout, paid or abandoned. A buyer's download link never expires, so resending one is always safe —
    it is the same link they were sent the first time.
    @if ($productCount === 0)
        <br><strong>Nothing is on sale yet</strong> — add a product before pointing anyone at the shop.
    @endif
</p>

<div class="card">
    <livewire:product-orders-table />
</div>
@endsection
