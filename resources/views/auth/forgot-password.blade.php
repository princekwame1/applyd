<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Applyd Academy</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="form-card">
                <h1 class="section-title" style="font-size: 1.5rem;">Forgot your password?</h1>
                <p class="section-lead" style="font-size: .95rem; margin-bottom: 24px;">
                    Give us the email on your account and we'll send you a link to set a new password.
                </p>

                @if (session('status'))
                    <div class="success-box">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="error-box">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label class="field-label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-brand" style="width: 100%;">Send reset link</button>
                </form>

                <p style="margin-top: 20px; font-size: .9rem; color: var(--ink-soft);">
                    <a href="{{ route('login') }}">← Back to sign in</a>
                </p>

                {{-- This site's login opens onto an admin-only area. Students
                     and facilitators reset here perfectly well, but they sign
                     in over there, so say it before they bounce off a 403. --}}
                <p style="margin-top: 8px; font-size: .85rem; color: var(--ink-soft);">
                    Students and facilitators sign in at the
                    <a href="{{ App\Support\Portal::loginUrl() }}" target="_blank" rel="noopener">learning portal</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
