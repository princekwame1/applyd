@extends('layouts.admin')

@section('title', 'Order '.$order->reference.' — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">{{ $order->product_title }}</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.product-orders') }}">← All orders</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card sv-flat" style="max-width:760px;">
    <div class="fin-headline">
        <div class="fin-headline-amount {{ $order->isPaid() ? 'fin-in' : '' }}">{{ $order->amount_label }}</div>
        <div class="fin-headline-meta">
            @if ($order->isPaid())
                <span class="badge badge-yes">Paid</span> {{ $order->paid_at?->format('M j, Y g:ia') }}
            @else
                <span class="badge badge-no">{{ ucfirst($order->status) }}</span> started {{ $order->created_at->format('M j, Y g:ia') }}
            @endif
        </div>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <div class="detail-item">
            <div class="lbl">Buyer</div>
            <div class="val">{{ $order->buyer_name }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Email</div>
            <div class="val"><a href="mailto:{{ $order->buyer_email }}">{{ $order->buyer_email }}</a></div>
        </div>
        <div class="detail-item">
            <div class="lbl">Phone</div>
            <div class="val">{{ $order->buyer_phone ?: '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Reference</div>
            <div class="val"><code>{{ $order->reference }}</code></div>
        </div>
        @if ((float) $order->fee > 0)
            <div class="detail-item">
                <div class="lbl">Payment charge</div>
                <div class="val">{{ \App\Models\Course::money((float) $order->fee) }} · passed to the buyer</div>
            </div>
        @endif
        <div class="detail-item">
            <div class="lbl">Downloads</div>
            <div class="val">{{ number_format($order->download_count) }}{{ $order->last_downloaded_at ? ' · last '.$order->last_downloaded_at->format('M j, Y g:ia') : '' }}</div>
        </div>
        <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="lbl">Product</div>
            <div class="val">
                @if ($order->product)
                    <a href="{{ route('shop.show', $order->product) }}" target="_blank" rel="noopener">{{ $order->product->title }}</a>
                @else
                    {{ $order->product_title }} <span style="color:var(--ink-soft);">(removed)</span>
                @endif
            </div>
        </div>
    </div>

    @if ($order->isPaid())
        {{-- The buyer's own link. Shown so support can hand it over on the
             phone, and resent by the button beside it. --}}
        <div style="margin-top:20px;">
            <label class="field-label">Their download link</label>
            <input type="text" value="{{ $order->download_url }}" readonly onclick="this.select()">
        </div>
    @endif

    <div class="modal-actions" style="margin-top:22px;">
        @if ($order->isPaid())
            <form method="POST" action="{{ route('dashboard.product-orders.resend', $order->id) }}" data-confirm="Send this buyer their download link again?">
                @csrf
                <button type="submit" class="btn btn-brand btn-sm">Resend download link</button>
            </form>
        @else
            <form method="POST" action="{{ route('dashboard.product-orders.mark-paid', $order->id) }}" data-confirm="Mark this order as paid? The buyer gets their download link straight away.">
                @csrf
                <button type="submit" class="btn btn-brand btn-sm">Mark as paid</button>
            </form>
        @endif
    </div>
</div>
@endsection
