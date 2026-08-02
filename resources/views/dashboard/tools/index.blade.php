@extends('layouts.admin')

@section('title', 'Tools — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Tools</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.tools.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Add Tool</h3>
    <form method="POST" action="{{ route('dashboard.tools.store') }}" enctype="multipart/form-data" class="schedule-form">
        @csrf
        <div>
            <label class="field-label" for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" placeholder="e.g. Figma" value="{{ old('name') }}" required>
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="category">Category <span class="req">*</span></label>
            <select id="category" name="category" required>
                @foreach (array_keys(config('bootcamp.category_short_names')) as $category)
                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            @error('category') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="grow">
            <label class="field-label" for="blurb">Blurb</label>
            <input type="text" id="blurb" name="blurb" placeholder="One short line about the tool" value="{{ old('blurb') }}">
            @error('blurb') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order') }}" placeholder="0">
            @error('sort_order') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp">
            @error('image') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="submit-cell">
            <button type="submit" class="btn btn-brand btn-sm">Add Tool</button>
        </div>
    </form>
</div>

<div class="card">
    <livewire:tools-table />
</div>
@endsection
