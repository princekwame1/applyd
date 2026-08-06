@extends('layouts.admin')

@section('title', 'Website Content — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Website Content</h1>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<p class="section-lead" style="font-size:.95rem; max-width:640px; margin-bottom:20px;">Edit the text and images on your public pages. Changes go live immediately.</p>

<div class="cms-page-grid">
    @foreach ($pages as $slug => $page)
        <a class="card cms-page-card" href="{{ route('dashboard.cms.edit', $slug) }}">
            <div class="cms-page-body">
                <h3>{{ $page['label'] }}</h3>
                <p>{{ collect($page['sections'])->flatMap(fn ($s) => $s['fields'])->count() }} editable fields · {{ count($page['sections']) }} sections</p>
            </div>
            <span class="cms-page-edit">Edit →</span>
        </a>
    @endforeach
</div>
@endsection
