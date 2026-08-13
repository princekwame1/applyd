@extends('layouts.admin')

@section('title', 'Session Videos — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Session Videos</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#videoCreateTpl" data-modal-title="Add Video">Add Video</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.videos.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<p style="color:var(--ink-soft); margin:-8px 0 18px; max-width:620px;">
    Recordings from past sessions, shown on the public
    <a href="{{ route('videos') }}" target="_blank" rel="noopener">Session Videos</a> page and the home page.
    Drag rows to change the order they appear in.
</p>

<div class="card">
    <livewire:session-videos-table />
</div>

<template id="videoCreateTpl">
    @include('dashboard.videos.partials.form', ['model' => null])
</template>
@endsection
