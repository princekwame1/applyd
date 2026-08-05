@extends('layouts.auth')

@section('title', 'Application Login — Applyd Academy')

@section('content')
<div class="form-card">
    <span class="auth-eyebrow">Applicant Portal</span>
    <h1 class="auth-title">Continue your application</h1>
    <p class="auth-sub">Sign in with the Serial Number and PIN sent to your phone after payment.</p>

    @if (session('status'))
        <div class="success-box" style="margin-bottom:16px;">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error-box" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('application.login.attempt') }}" class="enroll-form">
        @csrf
        <div>
            <label class="field-label" for="serial_no">Serial Number <span class="req">*</span></label>
            <input type="text" id="serial_no" name="serial_no" value="{{ old('serial_no') }}" placeholder="e.g. APPLYD2645561" required autofocus>
        </div>
        <div>
            <label class="field-label" for="pin">PIN <span class="req">*</span></label>
            <input type="text" id="pin" name="pin" placeholder="Your 10-digit PIN" required>
        </div>
        <button type="submit" class="btn btn-brand" style="width:100%; margin-top:4px;">Sign In</button>
    </form>

    <p class="auth-foot">Haven't registered yet? <a href="{{ route('courses') }}" class="tool-link">Browse courses</a></p>
</div>
@endsection
