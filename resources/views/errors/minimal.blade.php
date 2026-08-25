@use('App\Support\Impersonation')
{{-- An error page renders no layout, which is exactly why it needs its own copy
     of the way out of an impersonation: walk into a 403 while viewing as
     someone else (an impersonated student hitting /dashboard does it every
     time) and the banner that carries the Stop button is nowhere on screen.

     Everything here is wrapped in rescue(): this same view renders the 500 and
     the 503, where the session may not have started and a throw would replace
     the error page with a blank one. --}}
@php
    $impersonating = rescue(fn () => Impersonation::active(), false, false);
    $viewingAs = $impersonating ? rescue(fn () => auth()->user()?->name, null, false) : null;
    $actor = $impersonating ? rescue(fn () => Impersonation::impersonator()?->name, null, false) : null;
    $styles = rescue(fn () => asset('css/app.css').'?v='.filemtime(public_path('css/app.css')), null, false);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') — Applyd Academy</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap">
    @if ($styles)
        <link rel="stylesheet" href="{{ $styles }}">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body>
    <main class="err-page">
        <div class="err-card">
            <span class="err-code">@yield('code')</span>
            <h1 class="err-title">@yield('message')</h1>
            <p class="err-lead">@yield('lead')</p>

            @if ($impersonating)
                <div class="err-imp">
                    <p class="err-imp-text">
                        You are viewing as <strong>{{ $viewingAs }}</strong>@if ($actor), signed in as {{ $actor }}@endif —
                        which is why this page is closed to you.
                    </p>
                    <a class="btn btn-brand" href="{{ route('impersonate.stop.get') }}">Stop impersonating &amp; go back to my account</a>
                </div>
            @else
                <div class="err-actions">
                    <a class="btn btn-brand" href="{{ url('/') }}">Back to the site</a>
                </div>
            @endif
        </div>
    </main>
</body>
</html>
