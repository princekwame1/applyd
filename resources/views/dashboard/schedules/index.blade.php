@extends('layouts.admin')

@section('title', 'Schedules — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">24-Day Journey Schedule</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.schedules.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Add Schedule Entry</h3>
    <form method="POST" action="{{ route('dashboard.schedules.store') }}" class="schedule-form">
        @csrf
        <div>
            <label class="field-label" for="week_label">Week Label <span class="req">*</span></label>
            <input type="text" id="week_label" name="week_label" placeholder="e.g. Weeks 1–2" value="{{ old('week_label') }}" required>
            @error('week_label') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="grow">
            <label class="field-label" for="focus">Focus <span class="req">*</span></label>
            <input type="text" id="focus" name="focus" placeholder="e.g. Task & Project Management — Trello, Basecamp, Notion" value="{{ old('focus') }}" required>
            @error('focus') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order') }}" placeholder="0">
            @error('sort_order') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="submit-cell">
            <button type="submit" class="btn btn-brand btn-sm">Add Entry</button>
        </div>
    </form>
</div>

<div class="card">
    <livewire:schedules-table />
</div>
@endsection
