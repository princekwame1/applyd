@extends('layouts.admin')

@section('title', 'Email Delivery')

@section('content')
<div class="page-head">
    <div>
        <h2 class="section-title">Email Delivery</h2>
        <p style="color: var(--ink-soft);">Every email the site has sent, with its delivery status. Resend any message that failed or that a registrant never received.</p>
    </div>
    <a class="btn btn-brand" href="{{ route('dashboard.email-logs.export') }}">Export Excel</a>
</div>

@if ($queue['enabled'])
    <div class="mailq">
        <div class="mailq-item"><strong>{{ number_format($queue['waiting']) }}</strong> waiting to go out</div>
        @if ($queue['limit'] > 0)
            <div class="mailq-item"><strong>{{ number_format($queue['used']) }}</strong> of {{ number_format($queue['limit']) }} sent this hour</div>
            <div class="mailq-item"><strong>{{ number_format($queue['remaining']) }}</strong> left in the hour</div>
            @if ($queue['waiting'] > 0 && $queue['remaining'] === 0)
                <div class="mailq-item">Next send in about {{ ceil($queue['resets_in'] / 60) }} min</div>
            @endif
        @else
            <div class="mailq-item">No hourly limit set</div>
        @endif

        @if ($queue['stalled'])
            <div class="mailq-warn">
                Mail has been waiting since {{ $queue['oldest']->diffForHumans() }} and there is still allowance left this hour —
                nothing is draining the queue. Check that the server's cron is running <code>php artisan schedule:run</code>.
            </div>
        @else
            <div class="mailq-note">
                Email is queued and released at up to {{ $queue['limit'] > 0 ? number_format($queue['limit']).' an hour' : 'full speed' }},
                so a large send can't trip the host's sending limit. Statuses move Queued → Sent on their own.
            </div>
        @endif
    </div>
@endif

@if (session('success'))
    <div class="success-box">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card">
    <livewire:email-logs-table />
</div>
@endsection
