@extends('layouts.admin')

@section('title', 'Edit Schedule — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Schedule Entry</h1>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('dashboard.schedules.update', $schedule) }}" style="display:grid; gap:16px;">
        @csrf
        @method('PUT')
        <div>
            <label class="field-label" for="week_label">Week Label <span class="req">*</span></label>
            <input type="text" id="week_label" name="week_label" value="{{ old('week_label', $schedule->week_label) }}" required>
            @error('week_label') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="focus">Focus <span class="req">*</span></label>
            <input type="text" id="focus" name="focus" value="{{ old('focus', $schedule->focus) }}" required>
            @error('focus') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $schedule->sort_order) }}">
            @error('sort_order') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.schedules') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
