@extends('layouts.admin')

@section('title', 'Facilitators — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Facilitators</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#facilitatorCreateTpl" data-modal-title="Add Facilitator">Add Facilitator</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.facilitators.export') }}">Export Excel</a>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($total) }}</div><div class="lbl">Facilitators</div></div>
    <div class="stat-card"><div class="num">{{ number_format($active) }}</div><div class="lbl">Signed in</div></div>
    <div class="stat-card"><div class="num">{{ number_format($awaiting) }}</div><div class="lbl">Never sent a login</div></div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

{{-- Same fold as the student logins panel, and for the same reason: the one
     thing that goes wrong here is a link pointing at a door that won't open. --}}
<details class="card sv-flat sv-disclose" style="margin-bottom:22px;">
    <summary class="sv-disclose-head">
        <span class="sv-disclose-title">Facilitator logins</span>
        <span class="sv-disclose-hint">{{ number_format($active) }} signed in · {{ number_format($awaiting) }} waiting</span>
        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </summary>

    <div class="sv-share-body" style="max-width:none;">
        <p>
            Facilitators teach in the <strong>learning portal</strong>, not here. Their classes, timetable,
            attendance registers, materials, assignments, marking and results all live there. Adding
            someone on this page creates their account and texts and emails them the way in.
        </p>
        <p style="color:var(--ink-soft); font-size:.86rem;">
            Which classes somebody teaches is set in the portal, when a class is created. A resend gives a
            new temporary password to anyone who hasn't set their own yet, so the previous one stops
            working; facilitators who already chose a password keep it.
        </p>

        <div class="sv-share-url" id="portalUrl">{{ App\Support\Portal::loginUrl() }}</div>
        <div class="sv-share-actions">
            <button type="button" class="btn btn-sm btn-brand" id="portalCopy">Copy sign-in link</button>
            <a class="btn btn-sm btn-outline" href="{{ App\Support\Portal::loginUrl() }}" target="_blank" rel="noopener">Open the portal</a>
        </div>
        @if (App\Support\Portal::pointsAtThisSite())
            <p class="error-box" style="margin-top:14px;">
                <strong>PORTAL_URL points at this site</strong>, so facilitators would be sent somewhere they
                can't sign in. Point it at the learning portal in <code>.env</code> before sending.
            </p>
        @endif
    </div>
</details>

<div class="card">
    <livewire:facilitators-table />
</div>

<template id="facilitatorCreateTpl">
    @include('dashboard.facilitators.partials.form', ['model' => null])
</template>
@endsection

@push('scripts')
{{-- ApplydQr lives in this file, not the layout — without it the copy button
     throws on click. Same include the course-registrations panel carries. --}}
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
