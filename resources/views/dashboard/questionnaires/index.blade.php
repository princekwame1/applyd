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



<div class="card">
    <livewire:questionnaires-table />
</div>

<template id="questionnaireCreateTpl">
    @include('dashboard.questionnaires.partials.questionnaire-form', ['model' => null])
</template>
@endsection
