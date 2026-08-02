<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Applyd Academy</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="form-card">
                <h1 class="section-title" style="font-size: 1.5rem;">Admin Login</h1>
                <p class="section-lead" style="font-size: .95rem; margin-bottom: 24px;">Sign in to view bootcamp registrations.</p>

                @if ($errors->any())
                    <div class="error-box">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label class="field-label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="field-label" for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <label class="chk" style="margin-bottom: 20px;">
                        <input type="checkbox" name="remember" value="1"> Remember me
                    </label>
                    <button type="submit" class="btn btn-brand" style="width: 100%;">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
