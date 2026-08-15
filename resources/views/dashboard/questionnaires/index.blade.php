@extends('layouts.admin')

@section('title', 'Questionnaires — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Questionnaires</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#questionnaireCreateTpl" data-modal-title="New Form">New Form</button>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:720px;">
    Build a form, add its questions — checkboxes, radio buttons, short answers, paragraphs, dropdowns,
    dates, file uploads — then share the link. Anyone with the link can fill it in; no account needed.
    Drag rows to reorder the list.
</p>

<div class="card">
    <livewire:questionnaires-table />
</div>

<template id="questionnaireCreateTpl">
    @include('dashboard.questionnaires.partials.questionnaire-form', ['model' => null])
</template>
@endsection
