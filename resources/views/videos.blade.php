@extends('layouts.app')

@section('title', cms('videos', 'hero_title').' — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">{{ cms('videos', 'hero_eyebrow') }}</span>
        <h1 class="section-title">{{ cms('videos', 'hero_title') }}</h1>
        <p class="section-lead">{!! cms_html('videos', 'hero_sub') !!}</p>
    </div>
</section>

<section>
    <div class="container">
        @if ($videos->isEmpty())
            <div class="card center" style="padding:48px;">
                <h3 style="margin-bottom:8px;">{{ cms('videos', 'empty_heading') }}</h3>
                <p style="color:var(--ink-soft);">{{ cms('videos', 'empty_text') }}</p>
            </div>
        @else
            @include('partials.video-grid', ['videos' => $videos])

            @if ($videos->hasPages())
                <div class="jobs-pagination" style="margin-top:44px;">
                    {{ $videos->links() }}
                </div>
            @endif
        @endif
    </div>
</section>

<section class="final-cta">
    <div class="container">
        <h2 class="section-title">{{ cms('videos', 'cta_heading') }}</h2>
        <p class="section-lead" style="margin-left:auto;margin-right:auto;">{!! cms_html('videos', 'cta_sub') !!}</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">{{ cms('videos', 'cta_button') }}</a>
    </div>
</section>
@endsection
