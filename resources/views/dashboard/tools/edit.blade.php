@extends('layouts.admin')

@section('title', 'Edit Tool — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Tool</h1>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('dashboard.tools.update', $tool) }}" enctype="multipart/form-data" style="display:grid; gap:16px;">
        @csrf
        @method('PUT')
        <div>
            <label class="field-label" for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $tool->name) }}" required>
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="category">Category <span class="req">*</span></label>
            <select id="category" name="category" required>
                @foreach (array_keys(config('bootcamp.category_short_names')) as $category)
                    <option value="{{ $category }}" @selected(old('category', $tool->category) === $category)>{{ $category }}</option>
                @endforeach
            </select>
            @error('category') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="blurb">Blurb</label>
            <input type="text" id="blurb" name="blurb" value="{{ old('blurb', $tool->blurb) }}">
            @error('blurb') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $tool->sort_order) }}">
            @error('sort_order') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="image">Image</label>
            <div style="display:flex; align-items:center; gap:14px;">
                <img src="{{ $tool->image_url }}" alt="" style="width:84px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #eee9e8;">
                <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp">
            </div>
            <div class="upload-hint">Leave empty to keep the current image{{ $tool->image ? '' : ' (category default)' }}.</div>
            @error('image') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.tools') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
