@extends('layouts.admin')

@section('title', 'Courses — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Courses</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.courses.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Add Course</h3>
    <form method="POST" action="{{ route('dashboard.courses.store') }}" enctype="multipart/form-data" class="schedule-form">
        @csrf
        <div class="grow">
            <label class="field-label" for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" placeholder="e.g. Certificate in Digital Productivity" value="{{ old('title') }}" required>
            @error('title') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="level">Level</label>
            <select id="level" name="level">
                <option value="">Select…</option>
                @foreach (\App\Models\Course::LEVELS as $level)
                    <option value="{{ $level }}" @selected(old('level') === $level)>{{ $level }}</option>
                @endforeach
            </select>
            @error('level') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="duration">Duration</label>
            <input type="text" id="duration" name="duration" placeholder="e.g. 8 weeks" value="{{ old('duration') }}">
            @error('duration') <div class="field-error">{{ $message }}</div> @enderror
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
        <div class="grow" style="flex-basis: 100%;">
            <label class="field-label" for="description">Description</label>
            <input type="text" id="description" name="description" placeholder="Short description of the course" value="{{ old('description') }}">
            @error('description') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="submit-cell">
            <button type="submit" class="btn btn-brand btn-sm">Add Course</button>
        </div>
    </form>
</div>

<div class="card">
    <livewire:courses-table />
</div>
@endsection
