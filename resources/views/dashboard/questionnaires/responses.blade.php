@extends('layouts.admin')

@section('title', $questionnaire->title.' responses — Questionnaires')

@section('content')
<div class="page-head">
    <h1 class="section-title">{{ $questionnaire->title }} — Responses</h1>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires.responses.export', $questionnaire) }}">Export Excel</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires.build', $questionnaire) }}">Questions</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires') }}">All Forms</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($total) }}</div>
        <div class="lbl">Responses</div>
        @if ($questionnaire->response_limit)
            <div class="stat-meta">of a {{ number_format($questionnaire->response_limit) }} limit</div>
        @endif
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($today) }}</div>
        <div class="lbl">Submitted Today</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($liveCount) }}</div>
        <div class="lbl">Live Questions</div>
        <div class="stat-meta">
            @if ($reason = $questionnaire->closedReason())
                {{ $reason }}
            @else
                <a href="{{ $questionnaire->publicUrl() }}" target="_blank" rel="noopener">Open the public form</a>
            @endif
        </div>
    </div>
</div>

<div class="card">
    @if (! $total)
        <p style="color:var(--ink-soft); margin:0;">
            Nothing submitted yet. Share <a href="{{ $questionnaire->publicUrl() }}" target="_blank" rel="noopener">{{ $questionnaire->publicUrl() }}</a>
            and the responses land here.
        </p>
    @else
        <livewire:questionnaire-responses-table :questionnaire-id="$questionnaire->id" :key="'responses-'.$questionnaire->id" />
    @endif
</div>
@endsection
