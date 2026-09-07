@extends('layouts.admin')

@section('title', $opening->title.' — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">{{ $opening->title }}</h1>
        <p style="color:var(--ink-soft);">
            <a href="{{ route('dashboard.companies.show', $opening->company) }}">{{ $opening->company?->name }}</a> ·
            posted {{ $opening->created_at->format('F j, Y') }} ·
            @if ($opening->isApproved())
                <span class="badge badge-yes">Approved</span>
            @elseif ($opening->isRejected())
                <span class="badge badge-no">Rejected</span>
            @else
                <span class="status-chip status-pending">Pending review</span>
            @endif
        </p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.job-postings') }}">Back to postings</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
@endif

{{-- Who is behind it, before what it says. An advert reads fine whether or
     not the employer is real — that is exactly why it is judged second. --}}
<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:14px;">Who is posting this</h3>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="lbl">Company</div>
            <div><a href="{{ route('dashboard.companies.show', $opening->company) }}">{{ $opening->company?->name }}</a></div>
        </div>
        <div class="detail-item">
            <div class="lbl">Verification</div>
            <div>
                @if ($opening->company?->isApproved())
                    <span class="badge badge-yes">Verified</span>
                @elseif ($opening->company?->isRejected())
                    <span class="badge badge-no">Rejected</span>
                @else
                    <span class="status-chip status-pending">Not yet verified</span>
                @endif
            </div>
        </div>
        <div class="detail-item">
            <div class="lbl">Ghana Card</div>
            <div>
                @if ($opening->company?->ghana_card)
                    <code>{{ $opening->company->ghana_card }}</code>
                @else
                    <span style="color:var(--ink-soft);">Not provided</span>
                @endif
            </div>
        </div>
        <div class="detail-item">
            <div class="lbl">Contact</div>
            <div>{{ $opening->company?->user?->name }} · <a href="mailto:{{ $opening->company?->user?->email }}">{{ $opening->company?->user?->email }}</a></div>
        </div>
    </div>

    @unless ($opening->company?->isApproved())
        <div class="mailq" style="margin-top:16px;">
            <div class="mailq-warn">
                This employer has not been verified. Approving the posting is still fine — it simply waits off the
                board until <a href="{{ route('dashboard.companies.show', $opening->company) }}">their account is verified</a>.
            </div>
        </div>
    @endunless
</div>

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:14px;">The posting</h3>
    <div class="detail-grid" style="margin-bottom:18px;">
        <div class="detail-item"><div class="lbl">Type</div><div>{{ $opening->type }}</div></div>
        <div class="detail-item"><div class="lbl">Sector</div><div>{{ $opening->sector ?: '—' }}</div></div>
        <div class="detail-item"><div class="lbl">Location</div><div>{{ $opening->location ?: '—' }}</div></div>
        <div class="detail-item"><div class="lbl">Salary</div><div>{{ $opening->salary_range ?: 'Not stated' }}</div></div>
        <div class="detail-item"><div class="lbl">Deadline</div><div>{{ $opening->deadline?->format('M j, Y') ?? 'None' }}</div></div>
        <div class="detail-item"><div class="lbl">Applicants</div><div>{{ number_format($opening->applications()->count()) }}</div></div>
    </div>

    <div class="rich-text">{!! $opening->description !!}</div>
</div>

@if ($opening->reviewed_at)
    <div class="card" style="margin-bottom:20px;">
        <h3 style="margin-bottom:10px;">Last decision</h3>
        <p style="color:var(--ink-soft); margin:0;">
            {{ $opening->status_label }} on {{ $opening->reviewed_at->format('F j, Y \a\t g:ia') }}
            @if ($opening->reviewer) by {{ $opening->reviewer->name }} @endif
        </p>
        @if ($opening->review_note)
            <blockquote style="margin:12px 0 0; padding:12px 16px; background:var(--bg-soft); border-left:3px solid var(--brand); border-radius:6px;">
                {{ $opening->review_note }}
            </blockquote>
        @endif
    </div>
@endif

<div class="card">
    <h3 style="margin-bottom:6px;">Decision</h3>
    <p style="color:var(--ink-soft); font-size:.92rem; margin-bottom:16px;">
        The employer is emailed either way. Approving puts it on the board as soon as their company is verified;
        rejecting sends them the reason, and editing the posting puts it back in this queue.
    </p>

    <form method="POST" action="{{ route('dashboard.job-postings.approve', $opening) }}"
          data-confirm="Approve “{{ $opening->title }}”? The employer is emailed.">
        @csrf
        <button type="submit" class="btn btn-brand" @disabled($opening->isApproved())>
            {{ $opening->isApproved() ? 'Already approved' : 'Approve this posting' }}
        </button>
    </form>

    <details style="margin-top:20px;">
        <summary style="cursor:pointer; font-weight:600;">Reject this posting</summary>
        <form method="POST" action="{{ route('dashboard.job-postings.reject', $opening) }}" style="margin-top:12px; max-width:620px;">
            @csrf
            <label for="reason">Why? This goes to them word for word.</label>
            <textarea id="reason" name="reason" rows="3" required minlength="10" maxlength="1000"
                      placeholder="e.g. The advert asks applicants to pay a registration fee. Remove that and resubmit and we will publish it."
                      style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:1rem;">{{ old('reason') }}</textarea>
            <button type="submit" class="btn btn-outline link-danger" style="margin-top:10px;">Reject and email them</button>
        </form>
    </details>
</div>
@endsection
