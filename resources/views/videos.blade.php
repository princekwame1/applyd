@extends('layouts.app')

@section('title', 'Session Videos — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">Watch</span>
        <h1 class="section-title">Sessions You Missed</h1>
        <p class="section-lead">Recordings from our previous bootcamp sessions — watch them any time, at your own pace.</p>
    </div>
</section>

<section>
    <div class="container">
        @if ($videos->isEmpty())
            <div class="card center" style="padding:48px;">
                <h3 style="margin-bottom:8px;">No recordings yet</h3>
                <p style="color:var(--ink-soft);">Session videos will show up here once the first ones are published.</p>
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
        <h2 class="section-title">Want to join the next one live?</h2>
        <p class="section-lead" style="margin-left:auto;margin-right:auto;">Reserve your spot in the next cohort and learn with the group.</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">Register Now →</a>
    </div>
</section>
@endsection
