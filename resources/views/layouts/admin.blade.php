<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — Applyd Academy')</title>
    <script>if (localStorage.getItem('sidebarCollapsed') === 'true') document.documentElement.classList.add('is-collapsed');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a class="side-logo" href="{{ route('dashboard') }}">
            <img class="side-logo-full" src="{{ asset('img/logo.png') }}" alt="Applyd Academy">
            <img class="side-logo-mark" src="{{ asset('favicon.png') }}" alt="Applyd">
        </a>

        <nav class="side-nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg> Dashboard</a>

            <span class="side-heading">Bootcamp</span>
            <a href="{{ route('dashboard.registrations') }}" class="{{ request()->routeIs('dashboard.registrations') || request()->routeIs('dashboard.show') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6a1 1 0 0 1 1 1v1H8V3a1 1 0 0 1 1-1z"/><path d="M16 4h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="14" y2="15"/></svg> Registrations</a>
            <a href="{{ route('dashboard.schedules') }}" class="{{ request()->routeIs('dashboard.schedules*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Schedules</a>
            <a href="{{ route('dashboard.tools') }}" class="{{ request()->routeIs('dashboard.tools*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg> Tools</a>

            <span class="side-heading">Academy</span>
            <a href="{{ route('dashboard.courses') }}" class="{{ request()->routeIs('dashboard.courses*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.5 2.5 6 2.5s6-1.5 6-2.5v-5"/></svg> Courses</a>

            @canany(['manage users', 'manage roles'])
                <span class="side-heading">Administration</span>
                @can('manage users')
                    <a href="{{ route('dashboard.users') }}" class="{{ request()->routeIs('dashboard.users*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Users</a>
                @endcan
                @can('manage roles')
                    <a href="{{ route('dashboard.roles') }}" class="{{ request()->routeIs('dashboard.roles*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Roles &amp; Permissions</a>
                @endcan
            @endcanany

            <span class="side-heading">General</span>
            <a href="{{ route('dashboard.sms-logs') }}" class="{{ request()->routeIs('dashboard.sms-logs*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> SMS Delivery</a>
            <a href="{{ route('landing') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> View Landing Page</a>
        </nav>
    </aside>

    <main class="admin-content">
        <header class="admin-topbar">
            <button id="sidebarToggle" class="sidebar-toggle" title="Toggle sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
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

    {{-- Shared modal for create/edit forms --}}
    <div class="modal-overlay" id="adminModal" hidden>
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="adminModalTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="adminModalTitle"></h3>
                <button type="button" class="modal-x" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="adminModalBody"></div>
        </div>
    </div>

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

        // Sidebar collapse toggle (state lives on <html> so it's set before paint — no flash)
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', function () {
            const collapsed = document.documentElement.classList.toggle('is-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed);
        });

        // Styled file input: live image preview + filename
        document.addEventListener('change', function (e) {
            var input = e.target.closest('input[type=file][data-preview]');
            if (!input) return;
            var scope = input.closest('form') || document;
            var id = input.getAttribute('data-preview');
            var preview = scope.querySelector('[data-preview-for="' + id + '"]');
            var nameEl = scope.querySelector('[data-filename-for="' + id + '"]');
            var file = input.files && input.files[0];
            if (nameEl) nameEl.textContent = file ? file.name : '';
            if (preview && file && file.type.indexOf('image/') === 0) {
                preview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="">';
            }
        });

        // Click an image preview to zoom it in a lightbox
        var lightbox = document.createElement('div');
        lightbox.className = 'img-lightbox';
        lightbox.hidden = true;
        lightbox.innerHTML = '<button type="button" class="lb-close" aria-label="Close">&times;</button><img src="" alt="Preview">';
        document.body.appendChild(lightbox);
        var lightboxImg = lightbox.querySelector('img');

        function closeLightbox() { lightbox.hidden = true; lightboxImg.src = ''; }

        document.addEventListener('click', function (e) {
            var img = e.target.closest('.file-preview img');
            if (!img) return;
            e.preventDefault();
            lightboxImg.src = img.src;
            lightbox.hidden = false;
        });
        lightbox.addEventListener('click', closeLightbox);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !lightbox.hidden) closeLightbox();
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

    {{-- Shared modal: open (template clone / AJAX), AJAX submit, inline validation --}}
    <script>
        (function () {
            var overlay = document.getElementById('adminModal');
            var body = document.getElementById('adminModalBody');
            var titleEl = document.getElementById('adminModalTitle');

            function initSelect2(scope) {
                if (!window.jQuery || !jQuery.fn.select2) return;
                jQuery(scope).find('select').filter(function () {
                    return !this.closest('[wire\\:id]') && !this.hasAttribute('data-no-select2');
                }).select2({ width: '100%', dropdownParent: jQuery(overlay) });
            }

            function open(title, html) {
                titleEl.textContent = title || '';
                body.innerHTML = html;
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
                initSelect2(body);
                var first = body.querySelector('input:not([type=hidden]), select, textarea');
                if (first) first.focus();
            }

            function close() {
                overlay.hidden = true;
                body.innerHTML = '';
                document.body.style.overflow = '';
            }

            document.addEventListener('click', function (e) {
                var trigger = e.target.closest('[data-modal-open]');
                if (trigger) {
                    e.preventDefault();
                    var title = trigger.getAttribute('data-modal-title') || '';
                    var tplSel = trigger.getAttribute('data-modal-template');
                    var url = trigger.getAttribute('data-modal-url');
                    if (tplSel) {
                        var tpl = document.querySelector(tplSel);
                        open(title, tpl ? tpl.innerHTML : '');
                    } else if (url) {
                        open(title, '<div style="padding:24px; color:var(--ink-soft);">Loading…</div>');
                        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.text(); })
                            .then(function (html) {
                                body.innerHTML = html;
                                initSelect2(body);
                                var f = body.querySelector('input:not([type=hidden]), select, textarea');
                                if (f) f.focus();
                            })
                            .catch(function () {
                                body.innerHTML = '<p style="padding:24px; color:var(--danger);">Failed to load the form.</p>';
                            });
                    }
                    return;
                }
                if (e.target.closest('[data-modal-close]') || e.target === overlay) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !overlay.hidden) close();
            });

            document.addEventListener('submit', function (e) {
                var form = e.target.closest('[data-modal-form]');
                if (!form || !overlay.contains(form)) return;
                e.preventDefault();

                form.querySelectorAll('[data-error]').forEach(function (el) { el.textContent = ''; });
                var btn = form.querySelector('button[type=submit]');
                if (btn) btn.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
                    .then(function (res) {
                        if (res.status === 200 && res.data.ok) {
                            close();
                            if (window.Livewire && Livewire.all) {
                                Livewire.all().forEach(function (c) { c.call('$refresh'); });
                            }
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true, icon: 'success', title: res.data.message || 'Saved' });
                        } else if (res.status === 422 && res.data.errors) {
                            Object.keys(res.data.errors).forEach(function (key) {
                                var base = key.split('.')[0];
                                var slot = form.querySelector('[data-error="' + base + '"]');
                                if (slot && !slot.textContent) slot.textContent = res.data.errors[key][0];
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: (res.data && res.data.message) || 'Something went wrong.' });
                        }
                    })
                    .catch(function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed. Please try again.' });
                    })
                    .finally(function () { if (btn) btn.disabled = false; });
            });
        })();
    </script>
</body>
</html>
