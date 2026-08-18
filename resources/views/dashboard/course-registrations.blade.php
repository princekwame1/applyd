@extends('layouts.admin')

@section('title', 'Course Registrations — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Course Registrations</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.course-registrations.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card">
        <div class="num">{{ number_format($stats['total']) }}</div>
        <div class="lbl">Total Registrations</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ number_format($stats['completed']) }}</div>
        <div class="lbl">Completed</div>
    </div>
    <div class="stat-card">
        <div class="num">GHS {{ number_format((float) $stats['form_revenue'], 2) }}</div>
        <div class="lbl">Form-fee Revenue</div>
    </div>
    <div class="stat-card">
        <div class="num">GHS {{ number_format((float) $stats['tuition_revenue'], 2) }}</div>
        <div class="lbl">Tuition Collected</div>
    </div>
</div>

{{-- What students are actually sent, so it can be checked and read out over
     the phone when someone can't find the email. --}}
<details class="card sv-flat sv-disclose" style="margin-bottom:22px;">
    <summary>
        <span class="sv-disclose-title">Student logins</span>
        <span class="sv-disclose-hint">{{ number_format($stats['credentials_sent']) }} sent · {{ number_format($stats['awaiting_credentials']) }} waiting</span>
        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </summary>

    <div class="sv-share-body" style="max-width:none;">
        <p>
            Finishing a registration issues a student ID and a portal login, sent by email and SMS.
            To send them again, use the <strong>paper-plane</strong> button on a row, or tick several
            and pick <strong>“Send login details to selected”</strong>. Filter by
            <strong>Login details → Ready to send</strong> to find everyone still waiting.
        </p>
        <p style="color:var(--ink-soft); font-size:.86rem;">
            A resend gives a new temporary password to anyone who hasn't set their own yet, so the
            previous one stops working. Students who already chose a password keep it.
        </p>

        <div class="sv-share-url" id="portalUrl">{{ App\Support\Portal::loginUrl() }}</div>
        <div class="sv-share-actions">
            <button type="button" class="btn btn-sm btn-brand" id="portalCopy">Copy sign-in link</button>
            <a class="btn btn-sm btn-outline" href="{{ App\Support\Portal::loginUrl() }}" target="_blank" rel="noopener">Open the portal</a>
        </div>
        @if (App\Support\Portal::pointsAtThisSite())
            <p class="error-box" style="margin-top:14px;">
                <strong>PORTAL_URL points at this site</strong>, so students would be sent somewhere they
                can't sign in. Point it at the learning portal in <code>.env</code> before sending.
            </p>
        @endif
    </div>
</details>

<div class="card">
    <livewire:course-enrollments-table />
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/qr-share.js') }}"></script>
<script>
(function () {
    var copy = document.getElementById('portalCopy');
    if (!copy) return;

    copy.addEventListener('click', function () {
        ApplydQr.copy(@json(App\Support\Portal::loginUrl())).then(function () {
            copy.textContent = 'Copied';
            setTimeout(function () { copy.textContent = 'Copy sign-in link'; }, 1800);
        });
    });
})();
</script>
@endpush
