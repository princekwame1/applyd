<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Digital Tools Bootcamp 2026 — Applyd Academy')</title>
    @include('partials.social-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @stack('head')
</head>
<body>
    <nav class="site-nav">
        <div class="container">
            <a href="{{ route('landing') }}" class="site-logo">
                <img src="{{ asset('img/logo.png') }}" alt="Applyd Academy">
            </a>
            <ul class="nav-links" id="primaryNav">
                <li><a href="{{ route('landing') }}" class="{{ request()->routeIs('landing') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                <li><a href="{{ route('jobs') }}" class="{{ request()->routeIs('jobs') ? 'active' : '' }}">Jobs</a></li>
                <li><a href="{{ route('courses') }}" class="{{ request()->routeIs('courses') ? 'active' : '' }}">Courses</a></li>
                <li><a href="{{ route('videos') }}" class="{{ request()->routeIs('videos') ? 'active' : '' }}">Videos</a></li>
                <li><a href="{{ route('blog') }}" class="{{ request()->routeIs('blog*') ? 'active' : '' }}">Blog</a></li>
                <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact Us</a></li>
                @guest
                    <li class="nav-cta-item"><a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a></li>
                @endguest
            </ul>
            @auth
                <details class="profile-menu" id="navProfileMenu">
                    <summary style="list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <span class="side-avatar" style="flex: 0 0 36px; height: 36px; font-size: .9rem;">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="">
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </span>
                        <span style="font-weight: 600; font-size: .9rem; color: var(--ink);">{{ auth()->user()->name }}</span>
                    </summary>
                    <div class="pm-dropdown" style="right: 0; left: auto;">
                        <a href="{{ route('profile.edit') }}">Profile Settings</a>
                        @if (auth()->user()->hasRole('admin|super'))
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Log Out</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="btn btn-brand btn-sm nav-login-btn">Login</a>
            @endauth
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="primaryNav">
                <span></span><span></span><span></span>
            </button>
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
                    <li><a href="{{ route('talent.create') }}">Drop your CV</a></li>
                    <li><a href="{{ route('courses') }}">Courses</a></li>
                    <li><a href="{{ route('videos') }}">Videos</a></li>
                    <li><a href="{{ route('blog') }}">Blog</a></li>
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
    <script>
        document.addEventListener('click', function (e) {
            var menu = document.getElementById('navProfileMenu');
            if (menu && menu.open && !menu.contains(e.target)) menu.open = false;
        });

        (function () {
            var toggle = document.getElementById('navToggle');
            var nav = document.getElementById('primaryNav');
            if (!toggle || !nav) return;
            function setOpen(open) {
                nav.classList.toggle('open', open);
                toggle.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            toggle.addEventListener('click', function () {
                setOpen(!nav.classList.contains('open'));
            });
            nav.addEventListener('click', function (e) {
                if (e.target.closest('a')) setOpen(false);
            });
            document.addEventListener('click', function (e) {
                if (nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
            });
        })();
    </script>
    @include('partials.whatsapp-float')
    @include('partials.impersonation-banner')


    @stack('scripts')
</body>
</html>
