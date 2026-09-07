@extends('layouts.admin')

@section('title', $company->name.' — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">{{ $company->name }}</h1>
        <p style="color:var(--ink-soft);">
            Registered {{ $company->created_at->format('F j, Y') }} ·
            @if ($company->isApproved())
                <span class="badge badge-yes">Verified</span>
            @elseif ($company->isRejected())
                <span class="badge badge-no">Rejected</span>
            @else
                <span class="status-chip status-pending">Pending review</span>
            @endif
        </p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.companies') }}">Back to companies</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
@endif

{{-- The identity document is the reason this page exists, so it leads. --}}
<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:14px;">Job poster identification</h3>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="lbl">Ghana Card number</div>
            <div style="font-size:1.15rem; font-weight:700; letter-spacing:.02em;">
                @if ($company->ghana_card)
                    <code>{{ $company->ghana_card }}</code>
                @else
                    <span style="color:var(--ink-soft); font-weight:500;">Not provided</span>
                @endif
            </div>
        </div>
        <div class="detail-item">
            <div class="lbl">Contact person</div>
            <div>{{ $company->user?->name ?? '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Email</div>
            <div><a href="mailto:{{ $company->user?->email }}">{{ $company->user?->email ?? '—' }}</a></div>
        </div>
        <div class="detail-item">
            <div class="lbl">Location</div>
            <div>{{ $company->location ?: '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Website</div>
            <div>
                @if ($company->website)
                    <a href="{{ $company->website }}" target="_blank" rel="noopener nofollow">{{ $company->website }}</a>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="detail-item">
            <div class="lbl">Jobs posted</div>
            <div>{{ number_format($company->openings->count()) }}</div>
        </div>
    </div>

    @if (! $company->ghana_card)
        <div class="mailq" style="margin-top:16px;">
            <div class="mailq-warn">
                No Ghana Card on file. Accounts that registered before identification was introduced have none —
                ask for it by email before verifying, rather than approving an account nobody has identified.
            </div>
        </div>
    @endif

    @if ($duplicates->isNotEmpty())
        <div class="mailq" style="margin-top:16px;">
            <div class="mailq-warn">
                <strong>This card is on {{ $duplicates->count() }} other {{ Str::plural('account', $duplicates->count()) }}.</strong>
                One person can genuinely run more than one business, so this is not automatically wrong — but read it before deciding:
                <ul style="margin:8px 0 0 18px;">
                    @foreach ($duplicates as $other)
                        <li>
                            <a href="{{ route('dashboard.companies.show', $other) }}">{{ $other->name }}</a>
                            — {{ $other->user?->name }} ({{ $other->status_label }})
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>

@if ($company->description)
    <div class="card" style="margin-bottom:20px;">
        <h3 style="margin-bottom:10px;">About the company</h3>
        <div class="rich-text">{!! $company->description !!}</div>
    </div>
@endif

@if ($company->reviewed_at)
    <div class="card" style="margin-bottom:20px;">
        <h3 style="margin-bottom:10px;">Last decision</h3>
        <p style="color:var(--ink-soft); margin:0;">
            {{ $company->status_label }} on {{ $company->reviewed_at->format('F j, Y \a\t g:ia') }}
            @if ($company->reviewer) by {{ $company->reviewer->name }} @endif
        </p>
        @if ($company->review_note)
            <blockquote style="margin:12px 0 0; padding:12px 16px; background:var(--bg-soft); border-left:3px solid var(--brand); border-radius:6px;">
                {{ $company->review_note }}
            </blockquote>
        @endif
    </div>
@endif

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:6px;">Decision</h3>
    <p style="color:var(--ink-soft); font-size:.92rem; margin-bottom:16px;">
        Either way the contact person is emailed. Verifying puts their approved postings on the public board;
        rejecting keeps everything off it and sends them the reason so they can fix it.
    </p>

    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
        <form method="POST" action="{{ route('dashboard.companies.approve', $company) }}"
              data-confirm="Verify {{ $company->name }}? Their approved postings go live and they get an email.">
            @csrf
            <button type="submit" class="btn btn-brand" @disabled($company->isApproved())>
                {{ $company->isApproved() ? 'Already verified' : 'Verify this company' }}
            </button>
        </form>

        <form method="POST" action="{{ route('dashboard.companies.resend', $company) }}">
            @csrf
            <button type="submit" class="btn btn-outline">Re-send registration email</button>
        </form>
    </div>

    <details style="margin-top:20px;">
        <summary style="cursor:pointer; font-weight:600;">Reject this company</summary>
        <form method="POST" action="{{ route('dashboard.companies.reject', $company) }}" style="margin-top:12px; max-width:620px;">
            @csrf
            <label for="reason">Why? This goes to them word for word.</label>
            <textarea id="reason" name="reason" rows="3" required minlength="10" maxlength="1000"
                      placeholder="e.g. The Ghana Card number does not match the contact name given. Send a photo of the card and we will take another look."
                      style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:1rem;">{{ old('reason') }}</textarea>
            <button type="submit" class="btn btn-outline link-danger" style="margin-top:10px;">Reject and email them</button>
        </form>
    </details>
</div>

@if ($company->openings->isNotEmpty())
    <div class="card">
        <h3 style="margin-bottom:14px;">Their postings</h3>
        <div class="table-wrap" style="box-shadow:none;">
            <table class="nice">
                <thead>
                    <tr><th>Title</th><th>Review</th><th>On the board</th><th>Posted</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($company->openings as $opening)
                        <tr>
                            <td><strong>{{ $opening->title }}</strong></td>
                            <td>
                                @if ($opening->isApproved())
                                    <span class="badge badge-yes">Approved</span>
                                @elseif ($opening->isRejected())
                                    <span class="badge badge-no">Rejected</span>
                                @else
                                    <span class="status-chip status-pending">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($opening->is_live)
                                    <span class="badge badge-yes">Live</span>
                                @else
                                    <span class="badge badge-no">No</span>
                                @endif
                            </td>
                            <td>{{ $opening->created_at->format('M j, Y') }}</td>
                            <td><a href="{{ route('dashboard.job-postings.show', $opening) }}">Review</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
