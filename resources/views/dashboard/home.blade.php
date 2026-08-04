@extends('layouts.admin')

@section('title', 'Dashboard — Applyd Academy')

@section('content')
<div class="dash-hero">
    <div class="dash-hero-text">
        <span class="dash-eyebrow">{{ now()->format('l, F j, Y') }}</span>
        <h1 class="section-title">Welcome back, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
        <p class="dash-hero-sub">Here's what's happening across Applyd Bootcamp &amp; Academy today.</p>
    </div>
    <a class="btn btn-primary dash-hero-cta" href="{{ route('dashboard.registrations') }}">View registrations</a>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <div class="num">{{ number_format($counts['registrations']) }}</div>
        <div class="lbl">Total Registrations</div>
    </div>
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <div class="num">{{ number_format($counts['today']) }}</div>
        <div class="lbl">Registered Today</div>
        <div class="stat-meta">{{ number_format($counts['week']) }} this week</div>
    </div>
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </span>
        <div class="num">{{ number_format($counts['tools']) }}</div>
        <div class="lbl">Tools</div>
    </div>
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </span>
        <div class="num">{{ number_format($counts['courses']) }}</div>
        <div class="lbl">Courses</div>
    </div>
</div>

<div class="dash-cols">
    <div class="dash-main">
        <div class="dash-block-head">
            <h2>Recent Registrations</h2>
            <a href="{{ route('dashboard.registrations') }}">View all</a>
        </div>
        <div class="card dash-recent">
            @forelse($recent as $r)
                <a class="recent-row" href="{{ route('dashboard.show', $r) }}">
                    <span class="recent-avatar">{{ strtoupper(mb_substr($r->full_name, 0, 1)) }}</span>
                    <span class="recent-info">
                        <span class="recent-name">{{ $r->full_name }}</span>
                        <span class="recent-sub">{{ $r->city ? $r->city.', ' : '' }}{{ $r->country }}</span>
                    </span>
                    <span class="recent-time">{{ $r->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="dash-empty">No registrations yet.</p>
            @endforelse
        </div>
    </div>

    <div class="dash-side">
        <div class="dash-block-head"><h2>Quick Links</h2></div>
        <div class="quick-links">
            <a class="quick-link" href="{{ route('dashboard.registrations') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                <span><strong>Registrations</strong><small>Search &amp; export sign-ups</small></span>
            </a>
            <a class="quick-link" href="{{ route('dashboard.schedules') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span><strong>Schedules</strong><small>The 24-day journey</small></span>
            </a>
            <a class="quick-link" href="{{ route('dashboard.tools') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span>
                <span><strong>Tools</strong><small>Bootcamp tool catalog</small></span>
            </a>
            <a class="quick-link" href="{{ route('dashboard.courses') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
                <span><strong>Courses</strong><small>Academy offerings</small></span>
            </a>
            @can('manage users')
                <a class="quick-link" href="{{ route('dashboard.users') }}">
                    <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span>
                    <span><strong>Users</strong><small>Accounts &amp; roles</small></span>
                </a>
            @endcan
            @can('manage roles')
                <a class="quick-link" href="{{ route('dashboard.roles') }}">
                    <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    <span><strong>Roles &amp; Permissions</strong><small>Access control</small></span>
                </a>
            @endcan
            <a class="quick-link" href="{{ route('dashboard.sms-logs') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <span><strong>SMS Delivery</strong><small>Message logs &amp; retries</small></span>
            </a>
            <a class="quick-link" href="{{ route('landing') }}">
                <span class="ql-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
                <span><strong>Landing Page</strong><small>See the public site</small></span>
            </a>
        </div>
    </div>
</div>
@endsection
