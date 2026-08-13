@extends('layouts.admin')

@section('title', 'Surveys — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Surveys</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#surveyCreateTpl" data-modal-title="New Survey">New Survey</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.questions') }}">Manage Questions</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys') }}">Results</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:680px;">
    Every check-in form you run. Each one keeps its own questions, its own link and QR code, and its own
    results — so a survey from a past session stays intact when you create the next one.
    Drag rows to set the order participants see on <a href="{{ route('surveys.index') }}" target="_blank" rel="noopener">the picker</a>.
</p>

<div class="card">
    <livewire:surveys-table />
</div>

<template id="surveyCreateTpl">
    @include('dashboard.surveys.partials.survey-form', ['model' => null])
</template>
@endsection
