@extends('layouts.company')

@section('title', 'Applications — '.$opening->title)

@section('content')
<a href="{{ route('company.home') }}" class="tool-link">← My jobs</a>
<div class="page-head" style="margin-top: 8px;">
    <h1 class="section-title">Applications: {{ $opening->title }}</h1>
    <span class="tag">{{ $applications->count() }} total</span>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

@forelse ($applications as $application)
    <div class="card applicant-card">
        <div class="applicant-head">
            <div>
                <h3>{{ $application->full_name }}</h3>
                <p class="applicant-meta">
                    <a href="mailto:{{ $application->email }}">{{ $application->email }}</a>
                    {{ $application->phone ? ' · '.$application->phone : '' }}
                    · Applied {{ $application->created_at->format('M j, Y g:ia') }}
                </p>
            </div>
            <span class="status-chip status-{{ $application->status }}">{{ ucfirst($application->status) }}</span>
        </div>

        @if ($application->cover_letter)
            <p class="applicant-cover">{{ $application->cover_letter }}</p>
        @endif

        <div class="applicant-files">
            <a class="file-pill" href="{{ route('company.applications.cv', $application) }}">📄 CV — {{ $application->cv_name }}</a>
            @foreach ($application->documents as $document)
                <a class="file-pill" href="{{ route('company.applications.document', $document) }}">📎 {{ $document->original_name }}</a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('company.applications.status', $application) }}" class="status-form">
            @csrf
            @method('PATCH')
            <label class="field-label" style="margin: 0;">Status:</label>
            <select name="status" data-no-select2 style="width:auto; padding: 6px 10px;">
                @foreach (\App\Models\JobApplication::STATUSES as $status)
                    <option value="{{ $status }}" @selected($application->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline">Update</button>
        </form>
    </div>
@empty
    <div class="card center" style="padding: 48px;">
        <h3 style="margin-bottom: 8px;">No applications yet</h3>
        <p style="color: var(--ink-soft);">Share your job link to reach more candidates:</p>
        <p style="margin-top: 8px;"><a class="tool-link" href="{{ route('jobs.show', $opening) }}">{{ route('jobs.show', $opening) }}</a></p>
    </div>
@endforelse
@endsection
