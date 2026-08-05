@extends('layouts.app')

@section('title', ($success ? 'Registration Complete' : 'Payment Incomplete').' — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">Course Registration</span>
        <h1 class="section-title">{{ $success ? 'You\'re registered! 🎉' : 'Payment not completed' }}</h1>
        <p class="section-lead">
            {{ $success
                ? 'Your registration form has been paid for successfully.'
                : 'We couldn\'t confirm your payment. If you were charged, please contact us with your reference.' }}
        </p>
    </div>
</section>

<section>
    <div class="container">
        <div class="form-card" style="max-width:520px;">
            <div class="enroll-result {{ $success ? 'is-success' : 'is-fail' }}">
                <span class="enroll-result-ic">
                    <i class="fa-solid {{ $success ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                </span>
            </div>

            @if ($success && $enrollment->serial_no)
                <div class="enroll-credentials">
                    <p>Keep these safe — we've also sent them to your phone. Use them to continue your application.</p>
                    <div class="cred-row"><span>Serial Number</span><strong>{{ $enrollment->serial_no }}</strong></div>
                    <div class="cred-row"><span>PIN</span><strong>{{ $enrollment->pin }}</strong></div>
                </div>
            @endif

            <table class="enroll-summary">
                <tr><th>Course</th><td>{{ $enrollment->course?->title ?? '—' }}</td></tr>
                <tr><th>Name</th><td>{{ $enrollment->name }}</td></tr>
                <tr><th>Email</th><td>{{ $enrollment->email }}</td></tr>
                <tr><th>Amount</th><td>{{ $enrollment->amount_label }}</td></tr>
                <tr><th>Reference</th><td>{{ $enrollment->reference }}</td></tr>
                <tr><th>Status</th><td>
                    <span class="badge {{ $success ? 'badge-yes' : 'badge-no' }}">{{ ucfirst($enrollment->status) }}</span>
                </td></tr>
            </table>

            <div style="display:flex; gap:10px; margin-top:22px; flex-wrap:wrap;">
                @if ($success)
                    <a href="{{ route('application.login') }}" class="btn btn-brand btn-sm">Continue application →</a>
                @endif
                @if ($enrollment->course)
                    <a href="{{ route('courses.show', $enrollment->course) }}" class="btn btn-outline btn-sm">Back to course</a>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
