@extends('layouts.app')

@section('title', $product->title.' — Applyd Academy')
@section('og_title', $product->title)
@section('og_description', $product->tagline ?: $product->short_description)
@section('og_image', $product->cover_url ?? '')
@section('og_type', 'product')

@section('content')
<section class="shop-detail">
    <div class="container">
        <a class="shop-back" href="{{ route('shop') }}">← All downloads</a>

        <div class="shop-detail-grid">
            <div class="shop-detail-main">
                @if ($product->cover_url)
                    <img class="shop-detail-cover" src="{{ $product->cover_url }}" alt="{{ $product->title }}">
                @endif

                @if ($product->category)<span class="shop-badge shop-badge-inline">{{ $product->category }}</span>@endif
                <h1 class="section-title" style="margin-bottom:10px;">{{ $product->title }}</h1>
                @if ($product->tagline)
                    <p class="section-lead" style="margin-bottom:22px;">{{ $product->tagline }}</p>
                @endif

                @if ($product->description)
                    <div class="shop-description job-description">{!! $product->description !!}</div>
                @endif
            </div>

            <aside class="shop-detail-aside">
                <div class="card shop-buy-box">
                    <div class="shop-buy-price {{ $product->isFree() ? 'is-free' : '' }}">{{ $product->price_label }}</div>

                    <div class="shop-buy-facts">
                        @if ($product->format)
                            <div class="shop-buy-row"><i class="fa-regular fa-file"></i> {{ $product->format }}</div>
                        @endif
                        @if ($product->file_size_label)
                            <div class="shop-buy-row"><i class="fa-solid fa-download"></i> {{ $product->file_size_label }}</div>
                        @endif
                        <div class="shop-buy-row"><i class="fa-solid fa-infinity"></i> Yours to keep — download it whenever</div>
                    </div>

                    @unless ($product->isFree())
                        @include('partials.paystack-fee', ['net' => (float) $product->price, 'label' => $product->title])
                    @endunless

                    <p class="enroll-note">{{ cms('shop', 'buy_note') }}</p>

                    @if (session('error'))
                        <div class="error-box" style="margin-bottom:12px;">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="error-box" style="margin-bottom:12px;">Please check the details below.</div>
                    @endif

                    <form method="POST" action="{{ route('shop.checkout', $product) }}" class="enroll-form">
                        @csrf
                        <div>
                            <label class="field-label" for="b_name">Full Name <span class="req">*</span></label>
                            <input type="text" id="b_name" name="buyer_name" value="{{ old('buyer_name') }}" required>
                            @error('buyer_name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label" for="b_email">Email <span class="req">*</span></label>
                            <input type="email" id="b_email" name="buyer_email" value="{{ old('buyer_email') }}" required>
                            <div class="upload-hint">This is where the download link goes — check it before you pay.</div>
                            @error('buyer_email') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label" for="b_phone">Phone</label>
                            <input type="tel" id="b_phone" name="buyer_phone" value="{{ old('buyer_phone') }}">
                            @error('buyer_phone') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        @if ($product->isFree())
                            <button type="submit" class="btn btn-brand" style="width:100%; margin-top:4px;">Get it free</button>
                        @else
                            @php($total = App\Support\PaystackFees::gross((float) $product->price))
                            <button type="submit" class="btn btn-brand" style="width:100%; margin-top:4px;">Pay {{ App\Models\Course::money($total) }} &amp; Download</button>
                            <p class="enroll-secure"><i class="fa-solid fa-lock"></i> Secure payment via Paystack</p>
                        @endif
                    </form>
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <div class="course-related">
                <h2 class="section-title" style="margin-bottom:20px;">You might also want</h2>
                <div class="shop-grid">
                    @foreach ($related as $rel)
                        <a class="card shop-card" href="{{ route('shop.show', $rel) }}">
                            <div class="shop-thumb">
                                @if ($rel->cover_url)
                                    <img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}" loading="lazy">
                                @else
                                    <div class="shop-thumb-placeholder"><i class="fa-regular fa-file-lines"></i></div>
                                @endif
                            </div>
                            <div class="shop-body">
                                <h3>{{ $rel->title }}</h3>
                                <p class="shop-blurb">{{ $rel->tagline ?: $rel->short_description }}</p>
                                <div class="shop-meta">
                                    <span class="shop-price {{ $rel->isFree() ? 'is-free' : '' }}">{{ $rel->price_label }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
