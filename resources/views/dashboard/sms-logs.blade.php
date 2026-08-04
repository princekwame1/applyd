@extends('layouts.admin')

@section('title', 'SMS Delivery Tracking')

@section('content')
<div class="page-head">
    <h2 class="section-title">SMS Delivery Tracking</h2>
    <p style="color: var(--ink-soft);">Monitor SMS delivery status and manually retry failed messages.</p>
</div>

<div class="card">
    <livewire:sms-logs-table />
</div>
@endsection
