@extends('layouts.company')

@section('title', 'Edit Job — '.$opening->title)

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Job</h1>

<div class="card" style="max-width: 760px;">
    <form method="POST" action="{{ route('company.jobs.update', $opening) }}" style="display:grid; gap:14px;">
        @csrf
        @method('PUT')
        <div class="form-grid" style="gap: 14px 16px;">
            <div>
                <label class="field-label" for="title">Job Title <span class="req">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $opening->title) }}" required>
                @error('title') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="type">Type <span class="req">*</span></label>
                <select id="type" name="type" required>
                    @foreach (\App\Models\JobOpening::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type', $opening->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('type') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="location">Location</label>
                <input type="text" id="location" name="location" value="{{ old('location', $opening->location) }}">
                @error('location') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="salary_range">Salary Range</label>
                <input type="text" id="salary_range" name="salary_range" value="{{ old('salary_range', $opening->salary_range) }}">
                @error('salary_range') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="deadline">Application Deadline</label>
                <input type="date" id="deadline" name="deadline" value="{{ old('deadline', $opening->deadline?->format('Y-m-d')) }}">
                @error('deadline') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div>
            <label class="field-label" for="description">Description <span class="req">*</span></label>
            <textarea id="description" name="description" rows="6" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:1rem; font-family:inherit;" required>{{ old('description', $opening->description) }}</textarea>
            @error('description') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="chk">
                <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $opening->is_open))>
                Accepting applications
            </label>
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('company.home') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
