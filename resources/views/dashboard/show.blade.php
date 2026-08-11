@extends('layouts.admin')

@section('title', $registration->full_name.' — Registration')

@section('content')
<p style="margin-bottom: 16px;"><a href="{{ route('dashboard') }}">← Back to all registrations</a></p>

@if (session('success'))
    <div class="success-box">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="page-head" style="margin-bottom: 28px;">
    <div>
        <h1 class="section-title" style="margin-bottom: 6px;">{{ $registration->full_name }}</h1>
        <p style="color: var(--ink-soft); margin: 0;">Registered {{ $registration->created_at->format('F j, Y \a\t g:ia') }}</p>
    </div>
    <form method="POST" action="{{ route('dashboard.registrations.resend-email', $registration) }}"
          data-confirm="Send the confirmation email to {{ $registration->email }} again?">
        @csrf
        <button type="submit" class="btn btn-brand">Resend confirmation email</button>
    </form>
</div>

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

<div class="card" style="margin-top: 20px;">
    <div class="card-head">
        <div><h3>Emails sent ({{ $emailLogs->count() }})</h3></div>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.email-logs') }}">All email delivery →</a>
    </div>

    @forelse ($emailLogs as $log)
        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:12px 0; border-top:1px solid #e2e8f0; flex-wrap:wrap;">
            <div style="min-width:0;">
                <div style="font-weight:600;">{{ $log->subject }}</div>
                <div style="color:var(--ink-soft); font-size:.85rem;">
                    {{ $log->created_at->format('M j, Y g:ia') }} · {{ $log->template_label }}
                    @if ($log->retry_count) · resent {{ $log->retry_count }}× @endif
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="status-chip status-{{ $log->status }}">{{ ucfirst($log->status) }}</span>
                <a class="btn btn-sm btn-outline" href="{{ route('dashboard.email-logs.show', $log) }}" target="_blank" rel="noopener">View</a>
            </div>
        </div>
    @empty
        <p style="color: var(--ink-soft);">No emails have been sent to this registrant yet.</p>
    @endforelse
</div>
@endsection
