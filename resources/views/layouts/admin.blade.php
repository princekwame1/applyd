<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — Applyd Academy')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a class="side-logo" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logo.png') }}" alt="Applyd Academy">
        </a>

        <nav class="side-nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

            <span class="side-heading">Bootcamp</span>
            <a href="{{ route('dashboard.registrations') }}" class="{{ request()->routeIs('dashboard.registrations') || request()->routeIs('dashboard.show') ? 'active' : '' }}">Registrations</a>
            <a href="{{ route('dashboard.schedules') }}" class="{{ request()->routeIs('dashboard.schedules*') ? 'active' : '' }}">Schedules</a>
            <a href="{{ route('dashboard.tools') }}" class="{{ request()->routeIs('dashboard.tools*') ? 'active' : '' }}">Tools</a>

            <span class="side-heading">Academy</span>
            <a href="{{ route('dashboard.courses') }}" class="{{ request()->routeIs('dashboard.courses*') ? 'active' : '' }}">Courses</a>

            @canany(['manage users', 'manage roles'])
                <span class="side-heading">Administration</span>
                @can('manage users')
                    <a href="{{ route('dashboard.users') }}" class="{{ request()->routeIs('dashboard.users*') ? 'active' : '' }}">Users</a>
                @endcan
                @can('manage roles')
                    <a href="{{ route('dashboard.roles') }}" class="{{ request()->routeIs('dashboard.roles*') ? 'active' : '' }}">Roles &amp; Permissions</a>
                @endcan
            @endcanany

            <span class="side-heading">General</span>
            <a href="{{ route('landing') }}">View Landing Page</a>
        </nav>
    </aside>

    <main class="admin-content">
        <header class="admin-topbar">
            <details class="profile-menu" id="profileMenu">
                <summary>
                    <span class="side-avatar">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </span>
                    <span class="pm-name">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->role_label }}</small>
                    </span>
                    <span class="pm-caret">▾</span>
                </summary>
                <div class="pm-dropdown">
                    <a href="{{ route('profile.edit') }}">Profile Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Log Out</button>
                    </form>
                </div>
            </details>
        </header>

        <div class="admin-page">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Select2 with search on all plain selects (skip Livewire-controlled ones)
        $(function () {
            $('select').filter(function () {
                return !this.closest('[wire\\:id]') && !this.hasAttribute('data-no-select2');
            }).select2({ width: '100%' });
        });
    </script>
    <script>
        document.addEventListener('click', function (e) {
            var menu = document.getElementById('profileMenu');
            if (menu && menu.open && !menu.contains(e.target)) menu.open = false;
        });

        // SweetAlert2 confirmation for any form with data-confirm
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed) return;
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: form.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c73a41',
                cancelButtonColor: '#5f605f',
                confirmButtonText: 'Yes, do it',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            });
        });
    </script>
</body>
</html>
