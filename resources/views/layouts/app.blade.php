<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Digital Tools Bootcamp 2026 — Applyd Academy')</title>
    <meta name="description" content="A free, hands-on learning experience: 24 digital tools, 24 expert facilitators, 3 countries, 24 days. 100% online and completely free.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @stack('head')
</head>
<body>
    <nav class="site-nav">
        <div class="container">
            <a href="{{ route('landing') }}" class="site-logo">
                <img src="{{ asset('img/logo.png') }}" alt="Applyd Academy">
            </a>
            <ul class="nav-links">
                <li><a href="{{ route('landing') }}" class="{{ request()->routeIs('landing') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                <li><a href="{{ route('jobs') }}" class="{{ request()->routeIs('jobs') ? 'active' : '' }}">Jobs</a></li>
                <li><a href="{{ route('courses') }}" class="{{ request()->routeIs('courses') ? 'active' : '' }}">Courses</a></li>
                <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact Us</a></li>
            </ul>
            <a href="{{ route('login') }}" class="btn btn-brand btn-sm">Login</a>
        </div>
    </nav>

    @yield('content')

    {{-- Footer hidden on the landing page for now --}}
    {{-- @if (!request()->routeIs('landing')) --}}
    <footer>
        <div class="container footer-grid">
            <div>
                <img src="{{ asset('img/logo.png') }}" alt="Applyd Academy" class="footer-logo">
                <p>A free, hands-on learning experience. 24 digital tools, 24 expert facilitators, 3 countries, 24 days. Master the tools. Accelerate your future.</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="{{ route('landing') }}">Home</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                    <li><a href="{{ route('jobs') }}">Jobs</a></li>
                    <li><a href="{{ route('courses') }}">Courses</a></li>
                    <li><a href="{{ route('landing') }}#register">Register</a></li>
                    <li><a href="{{ route('contact') }}">Contact Us</a></li>
                    <li><a href="{{ route('login') }}">Login</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li><a href="mailto:info@applydacademy.com">info@applydacademy.com</a></li>
                    <li><a href="mailto:support@applydacademy.com">support@applydacademy.com</a></li>
                    <li>Trade Fair, 25 Giffard Rd, Accra</li>
                </ul>
            </div>
            <div>
                <h4>Follow Us</h4>
                <ul>
                    @foreach (config('bootcamp.social_links') as $network => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener">{{ $network }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">&copy; {{ date('Y') }} Applyd Academy — Master the Tools. Accelerate Your Future.</div>
        </div>
    </footer>
    {{-- @endif --}}

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>
</html>
