@extends('layouts.admin')

@section('title', 'Edit Course — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Course</h1>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('dashboard.courses.update', $course) }}" enctype="multipart/form-data" style="display:grid; gap:16px;">
        @csrf
        @method('PUT')
        <div>
            <label class="field-label" for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required>
            @error('title') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="level">Level</label>
            <select id="level" name="level">
                <option value="">Select…</option>
                @foreach (\App\Models\Course::LEVELS as $level)
                    <option value="{{ $level }}" @selected(old('level', $course->level) === $level)>{{ $level }}</option>
                @endforeach
            </select>
            @error('level') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="duration">Duration</label>
            <input type="text" id="duration" name="duration" value="{{ old('duration', $course->duration) }}">
            @error('duration') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="description">Description</label>
            <textarea id="description" name="description" rows="4" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:1rem; font-family:inherit;">{{ old('description', $course->description) }}</textarea>
            @error('description') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $course->sort_order) }}">
            @error('sort_order') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="image">Image</label>
            <div style="display:flex; align-items:center; gap:14px;">
                @if ($course->image_url)
                    <img src="{{ $course->image_url }}" alt="" style="width:84px; height:56px; object-fit:cover; border-radius:8px; border:1px solid #eee9e8;">
                @endif
                <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp">
            </div>
            <div class="upload-hint">Leave empty to keep the current image.</div>
            @error('image') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.courses') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
