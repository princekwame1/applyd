@extends('layouts.admin')

@section('title', 'SMS Delivery Tracking')

@section('content')
<div class="page-head">
    <h2 class="section-title">SMS Delivery Tracking</h2>
    <p style="color: var(--ink-soft);">Monitor SMS delivery status and manually retry failed messages.</p>
</div>

@if (session('success'))
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        {{ session('error') }}
    </div>
@endif

<div class="table-wrap">
    <table class="nice">
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Message</th>
                <th>Status</th>
                <th>Retries</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($smsLogs as $log)
                <tr>
                    <td style="font-size: .9rem;">{{ $log->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if ($log->registration)
                            <strong>{{ $log->registration->full_name }}</strong>
                        @else
                            <span style="color: var(--ink-soft);">—</span>
                        @endif
                    </td>
                    <td style="font-size: .9rem;">{{ $log->phone_number }}</td>
                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($log->message, 60) }}</td>
                    <td>
                        <span class="status-chip status-{{ $log->status }}">{{ ucfirst($log->status) }}</span>
                    </td>
                    <td style="text-align: center;">{{ $log->retry_count }}</td>
                    <td>
                        @if ($log->status === 'failed')
                            <form action="{{ route('dashboard.sms-logs.retry', $log) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-brand btn-sm" style="padding: 6px 12px; font-size: .85rem;">Retry</button>
                            </form>
                        @else
                            <span style="color: var(--ink-soft); font-size: .9rem;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--ink-soft);">No SMS logs yet</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($smsLogs->hasPages())
    <div class="pagination" style="margin-top: 24px;">
        {{ $smsLogs->links() }}
    </div>
@endif
@endsection
