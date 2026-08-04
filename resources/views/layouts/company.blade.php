<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Company Portal — Applyd Academy')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a class="side-logo" href="{{ route('company.home') }}">
            <img src="{{ asset('img/logo.png') }}" alt="Applyd Academy">
        </a>

        <nav class="side-nav">
            <a href="{{ route('company.home') }}" class="{{ request()->routeIs('company.home') ? 'active' : '' }}">My Jobs</a>

            <span class="side-heading">General</span>
            <a href="{{ route('jobs') }}">View Job Board</a>
            <a href="{{ route('landing') }}">Applyd Academy Site</a>
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
                        <strong>{{ auth()->user()->company->name ?? auth()->user()->name }}</strong>
                        <small>Company</small>
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
        $(function () {
            $('select').filter(function () {
                return !this.hasAttribute('data-no-select2');
            }).select2({ width: '100%' });
        });

        document.addEventListener('click', function (e) {
            var menu = document.getElementById('profileMenu');
            if (menu && menu.open && !menu.contains(e.target)) menu.open = false;
        });

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
