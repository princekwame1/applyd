@extends('layouts.app')

@section('title', 'Thank you — '.$questionnaire->title)

@section('content')
<section class="qform-wrap">
    <div class="container qform-narrow">
        <div class="qform-done">
            <div class="qform-done-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1 class="qform-title">Thank you</h1>
            <p class="qform-lead">{{ $questionnaire->thanks_message }}</p>
            <p class="qform-reference">Your reference: <strong>{{ $reference }}</strong></p>
            <a class="btn btn-brand" href="{{ route('landing') }}">Back to Applyd Academy</a>
        </div>
    </div>
</section>
@endsection
