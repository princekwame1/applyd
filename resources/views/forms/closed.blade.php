@extends('layouts.app')

@section('title', $questionnaire->title.' — Applyd Academy')

@section('content')
<section class="qform-wrap">
    <div class="container qform-narrow">
        <div class="qform-done">
            <div class="qform-done-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h1 class="qform-title">{{ $questionnaire->title }}</h1>
            <p class="qform-lead">{{ $reason }}</p>
            <a class="btn btn-brand" href="{{ route('landing') }}">Back to Applyd Academy</a>
        </div>
    </div>
</section>
@endsection
