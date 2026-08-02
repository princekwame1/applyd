@extends('layouts.admin')

@section('title', $registration->full_name.' — Registration')

@section('content')
<p style="margin-bottom: 16px;"><a href="{{ route('dashboard') }}">← Back to all registrations</a></p>

<h1 class="section-title" style="margin-bottom: 6px;">{{ $registration->full_name }}</h1>
<p style="color: var(--ink-soft); margin-bottom: 28px;">Registered {{ $registration->created_at->format('F j, Y \a\t g:ia') }}</p>

<div class="detail-grid" style="margin-bottom: 24px;">
    <div class="detail-item"><div class="lbl">Gender</div><div class="val">{{ $registration->gender }}</div></div>
    <div class="detail-item"><div class="lbl">Age Range</div><div class="val">{{ $registration->age_range }}</div></div>
    <div class="detail-item"><div class="lbl">Location</div><div class="val">{{ $registration->country }} — {{ $registration->city }}</div></div>
    <div class="detail-item"><div class="lbl">Phone</div><div class="val">{{ $registration->full_phone }}</div></div>
    <div class="detail-item"><div class="lbl">Email</div><div class="val">{{ $registration->email }}</div></div>
    <div class="detail-item"><div class="lbl">Level of Education</div><div class="val">{{ $registration->education }}</div></div>
    <div class="detail-item">
        <div class="lbl">Marketing Opt-in</div>
        <div class="val">
            @if ($registration->marketing_opt_in)
                <span class="badge badge-yes">Opted in</span>
            @else
                <span class="badge badge-no">Not opted in</span>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <h3>Tools They Want to Learn ({{ count($registration->tools ?? []) }})</h3>
    <div class="tool-tags">
        @forelse ($registration->tools ?? [] as $tool)
            <span class="tag">{{ $tool }}</span>
        @empty
            <p style="color: var(--ink-soft);">None selected.</p>
        @endforelse
    </div>
</div>
@endsection
