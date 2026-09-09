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
        @if ($notLive)
            <span class="status-chip status-pending" style="margin-left:8px;">Not live</span>
            <br><span style="font-size:.88rem;">{{ $notLive }}</span>
        @else
            <span class="status-chip status-sent" style="margin-left:8px;">Live</span>
        @endif
    </p>
</div>

{{-- Its own card, not the CMS one: those carry a single line of text, so
     sitting the Edit link beside the copy costs them nothing. Here the
     description is the useful part — when this email actually fires — and a
     column stolen from it squeezed every card into a tall ribbon. --}}
<div class="mail-tpl-grid">
    @foreach ($templates as $key => $template)
        <a class="card mail-tpl-card @if (! $template['enabled']) is-off @endif"
           href="{{ route('dashboard.email-templates.edit', $key) }}">
            <h3 class="mail-tpl-title">{{ $template['label'] }}</h3>
            <p class="mail-tpl-desc">{{ $template['description'] }}</p>
            <div class="mail-tpl-foot">
                {{-- Off, edited and untouched are three different states and
                     used to wear two badges between them. --}}
                @if (! $template['enabled'])
                    <span class="badge badge-off">Disabled</span>
                @elseif ($template['customised'])
                    <span class="badge badge-yes">Customised</span>
                @else
                    <span class="badge badge-no">Default copy</span>
                @endif
                <span class="mail-tpl-edit">Edit →</span>
            </div>
        </a>
    @endforeach
</div>
@endsection
