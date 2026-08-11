@extends('layouts.admin')

@section('title', 'Email Templates — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">Email Templates</h1>
        <p style="color: var(--ink-soft);">Customise the wording of the automatic emails the site sends.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('dashboard.email-logs') }}">Email Delivery →</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-bottom:8px;">Outgoing mail</h3>
    <p style="color: var(--ink-soft); font-size:.92rem; margin:0;">
        Mailer: <strong>{{ $mailer }}</strong> ·
        From: <strong>{{ $fromAddress ?: 'not configured' }}</strong>
        @if ($mailer === 'log' || ! $fromAddress)
            <span class="status-chip status-pending" style="margin-left:8px;">Not live</span>
            <br><span style="font-size:.88rem;">Set the cPanel SMTP values (<code>MAIL_MAILER=smtp</code>, <code>MAIL_HOST</code>, <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code>) in <code>.env</code> to start delivering real email.</span>
        @else
            <span class="status-chip status-sent" style="margin-left:8px;">Live</span>
        @endif
    </p>
</div>

<div class="cms-page-grid">
    @foreach ($templates as $key => $template)
        <a class="card cms-page-card" href="{{ route('dashboard.email-templates.edit', $key) }}">
            <div class="cms-page-body">
                <h3>{{ $template['label'] }}</h3>
                <p>{{ $template['description'] }}</p>
                <p style="margin-top:10px;">
                    @if (! $template['enabled'])
                        <span class="badge badge-no">Disabled</span>
                    @elseif ($template['customised'])
                        <span class="badge badge-yes">Customised</span>
                    @else
                        <span class="badge badge-no">Default copy</span>
                    @endif
                </p>
            </div>
            <span class="cms-page-edit">Edit →</span>
        </a>
    @endforeach
</div>
@endsection
