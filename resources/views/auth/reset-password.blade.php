<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set a New Password — Applyd Academy</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="form-card">
                <h1 class="section-title" style="font-size: 1.5rem;">Set a new password</h1>
                <p class="section-lead" style="font-size: .95rem; margin-bottom: 24px;">
                    Choose something you haven't used here before. At least 8 characters.
                </p>

                @if ($errors->any())
                    <div class="error-box">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div style="margin-bottom: 16px;">
                        <label class="field-label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="field-label" for="password">New password</label>
                        <input type="password" id="password" name="password" required autofocus>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label class="field-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-brand" style="width: 100%;">Save new password</button>
                </form>

                <p style="margin-top: 20px; font-size: .9rem; color: var(--ink-soft);">
                    <a href="{{ route('login') }}">← Back to sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
