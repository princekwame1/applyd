@extends('layouts.app')

@section('title', cms('shop', 'hero_title').' — Applyd Academy')
@section('og_title', cms('shop', 'hero_title') ?? '')
@section('og_description', strip_tags((string) cms_html('shop', 'hero_sub')))

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">{{ cms('shop', 'hero_eyebrow') }}</span>
        <h1 class="section-title">{{ cms('shop', 'hero_title') }}</h1>
        <p class="section-lead">{!! cms_html('shop', 'hero_sub') !!}</p>
    </div>
</section>

<section>
    <div class="container">
        @if (session('status'))
            <div class="success-box" style="margin-bottom:22px;">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-box" style="margin-bottom:22px;">{{ session('error') }}</div>
        @endif

        @if ($products->isEmpty())
            <div class="card center" style="padding:48px;">
                <h3 style="margin-bottom:8px;">{{ cms('shop', 'empty_heading') }}</h3>
                <p style="color:var(--ink-soft);">{{ cms('shop', 'empty_text') }}</p>
            </div>
        @else
            @if ($categories->count() > 1)
                {{-- Filtering happens in the browser over the page you already
                     have: with JS off every card simply stays visible. --}}
                <div class="shop-filters" id="shopFilters">
                    <button type="button" class="shop-filter is-active" data-shop-filter="">All</button>
                    @foreach ($categories as $category)
                        <button type="button" class="shop-filter" data-shop-filter="{{ $category }}">{{ $category }}</button>
                    @endforeach
                </div>
            @endif

            <div class="shop-grid">
                @foreach ($products as $product)
                    <a class="card shop-card" href="{{ route('shop.show', $product) }}" data-category="{{ $product->category }}">
                        <div class="shop-thumb">
                            @if ($product->cover_url)
                                <img src="{{ $product->cover_url }}" alt="{{ $product->title }}" loading="lazy">
                            @else
                                <div class="shop-thumb-placeholder"><i class="fa-regular fa-file-lines"></i></div>
                            @endif
                            @if ($product->category)<span class="shop-badge">{{ $product->category }}</span>@endif
                        </div>
                        <div class="shop-body">
                            <h3>{{ $product->title }}</h3>
                            <p class="shop-blurb">{{ $product->tagline ?: $product->short_description }}</p>
                            <div class="shop-meta">
                                @if ($product->format)
                                    <span class="shop-meta-item"><i class="fa-regular fa-file"></i> {{ $product->format }}</span>
                                @endif
                                <span class="shop-price {{ $product->isFree() ? 'is-free' : '' }}">{{ $product->price_label }}</span>
                            </div>
                            <span class="btn btn-brand btn-sm shop-cta">{{ $product->isFree() ? 'Get it free' : 'View & buy' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="jobs-pagination" style="margin-top:44px;">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
</section>

<section class="final-cta">
    <div class="container">
        <h2 class="section-title">{{ cms('shop', 'cta_heading') }}</h2>
        <p class="section-lead" style="margin-left:auto;margin-right:auto;">{!! cms_html('shop', 'cta_sub') !!}</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">{{ cms('shop', 'cta_button') }}</a>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var bar = document.getElementById('shopFilters');
        if (!bar) return;
        var cards = document.querySelectorAll('.shop-card');

        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-shop-filter]');
            if (!btn) return;
            var wanted = btn.getAttribute('data-shop-filter');

            bar.querySelectorAll('.shop-filter').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            cards.forEach(function (card) {
                card.style.display = (!wanted || card.dataset.category === wanted) ? '' : 'none';
            });
        });
    });
</script>
@endpush
