@extends('layouts.admin')

@section('title', 'Dashboard — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Welcome back, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ $counts['registrations'] }}</div><div class="lbl">Total Registrations</div></div>
    <div class="stat-card"><div class="num">{{ $counts['today'] }}</div><div class="lbl">Registered Today</div></div>
    <div class="stat-card"><div class="num">{{ $counts['tools'] }}</div><div class="lbl">Tools</div></div>
    <div class="stat-card"><div class="num">{{ $counts['courses'] }}</div><div class="lbl">Courses</div></div>
</div>

<div class="home-grid">
    <a class="card home-card" href="{{ route('dashboard.registrations') }}">
        <h3>Registrations</h3>
        <p>View, search, and export bootcamp sign-ups.</p>
        <span>Open →</span>
    </a>
    <a class="card home-card" href="{{ route('dashboard.schedules') }}">
        <h3>Schedules</h3>
        <p>Manage the 24-day journey shown on the landing page.</p>
        <span>Open →</span>
    </a>
    <a class="card home-card" href="{{ route('dashboard.tools') }}">
        <h3>Tools</h3>
        <p>Add or edit the tools taught in the bootcamp.</p>
        <span>Open →</span>
    </a>
    <a class="card home-card" href="{{ route('dashboard.courses') }}">
        <h3>Courses</h3>
        <p>Manage Applyd Academy course offerings.</p>
        <span>Open →</span>
    </a>
    @can('manage users')
        <a class="card home-card" href="{{ route('dashboard.users') }}">
            <h3>Users</h3>
            <p>Create accounts and assign roles.</p>
            <span>Open →</span>
        </a>
    @endcan
    @can('manage roles')
        <a class="card home-card" href="{{ route('dashboard.roles') }}">
            <h3>Roles &amp; Permissions</h3>
            <p>Define what each role can access.</p>
            <span>Open →</span>
        </a>
    @endcan
    <a class="card home-card" href="{{ route('profile.edit') }}">
        <h3>Profile</h3>
        <p>Update your photo, details, and password.</p>
        <span>Open →</span>
    </a>
    <a class="card home-card" href="{{ route('landing') }}">
        <h3>Landing Page</h3>
        <p>See the public site as visitors do.</p>
        <span>Open →</span>
    </a>
</div>
@endsection
