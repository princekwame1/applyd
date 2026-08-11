@extends('layouts.app')

@section('title', 'Thanks — Pulse Check')

@section('content')
<section class="pulse-wrap">
    <div class="container pulse-narrow pulse-done">
        <span class="pulse-done-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
        <h1 class="pulse-done-title">All set</h1>
        <p class="pulse-done-msg">{{ $copy['thanks'] }}</p>
        <a class="btn btn-outline" href="{{ route('surveys.index') }}">Done</a>
    </div>
</section>
@endsection
