@extends('layouts.admin')

@section('title', 'Blog — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Blog</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#postCreateTpl" data-modal-title="New Post">New Post</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.blog.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card">
    <livewire:posts-table />
</div>

<div class="card" style="margin-top:24px; max-width:640px;">
    <div class="card-head">
        <span class="card-ic">@include('partials.icon', ['d' => 'research'])</span>
        <div>
            <h3>Categories</h3>
            <p>Organize posts. Deleting a category leaves its posts uncategorized.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('dashboard.blog.categories.store') }}" style="display:flex; gap:10px; align-items:flex-start; margin-bottom:18px;">
        @csrf
        <div style="flex:1;">
            <input type="text" name="name" placeholder="e.g. Marketing Tips" value="{{ old('name') }}" required>
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-brand btn-sm" style="white-space:nowrap;">Add</button>
    </form>

    <div class="cat-chips">
        @forelse ($categories as $category)
            <span class="cat-chip">
                {{ $category->name }}
                <form method="POST" action="{{ route('dashboard.blog.categories.destroy', $category) }}" data-confirm="Delete the '{{ $category->name }}' category?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" aria-label="Delete category" title="Delete">&times;</button>
                </form>
            </span>
        @empty
            <p style="color:var(--ink-soft); font-size:.9rem;">No categories yet.</p>
        @endforelse
    </div>
</div>

<template id="postCreateTpl">
    @include('dashboard.blog.partials.form', ['model' => null, 'categories' => $categories])
</template>
@include('partials.quill')
@endsection
