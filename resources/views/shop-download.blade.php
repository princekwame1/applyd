@extends('layouts.app')

@section('title', 'Your download — '.$order->product_title)

@section('content')
<section class="shop-detail">
    <div class="container">
        <div class="card shop-done">
            <div class="qform-done-mark"><i class="fa-solid fa-check"></i></div>
            <h1 class="section-title" style="margin-bottom:8px;">Thanks, {{ $order->first_name }}. It's all yours</h1>
            <p class="section-lead" style="margin-bottom:26px;">
                <strong>{{ $order->product_title }}</strong> is ready. We've also emailed this page to
                {{ $order->buyer_email }}, so you can come back to it any time.
            </p>

            @if ($product && $product->deliversFile())
                <a class="btn btn-brand" href="{{ route('shop.download.file', $order->download_token) }}">
                    <i class="fa-solid fa-download"></i> Download{{ $product->file_size_label ? ' ('.$product->file_size_label.')' : '' }}
                </a>
            @elseif ($product && $product->deliversLink())
                <a class="btn btn-brand" href="{{ $product->external_url }}" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open your download
                </a>
            @else
                {{-- The product's file has gone but the sale stands: say so
                     plainly rather than showing a button that leads nowhere. --}}
                <div class="error-box">
                    We can't find the file for this order. Email us with reference {{ $order->reference }} and we'll send it straight over.
                </div>
            @endif

            <p class="qform-reference" style="margin-top:24px;">
                Reference {{ $order->reference }} · {{ $order->amount_label }}
                @if ($order->paid_at) · {{ $order->paid_at->format('M j, Y') }} @endif
            </p>
            <p class="shop-done-note">
                Keep this link to yourself. Anyone who has it can download the file.
            </p>

            <a class="shop-back" style="margin-top:18px; display:inline-block;" href="{{ route('shop') }}">← Browse other downloads</a>
        </div>
    </div>
</section>
@endsection
