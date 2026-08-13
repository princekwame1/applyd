{{-- /profile is shared by every signed-in user, so the chrome around it has to
     follow the role: a recruiter gets their own portal nav, not the admin
     sidebar full of links they can't open. --}}
@extends(auth()->user()?->hasRole('company') ? 'layouts.company' : 'layouts.admin')

@section('title', 'Profile — Applyd Academy')

@section('content')
@php($user = auth()->user())

<div class="page-head">
    <h1 class="section-title">Profile Settings</h1>
</div>

@if (session('status_profile'))
    <div class="success-box" style="max-width: 980px;">{{ session('status_profile') }}</div>
@endif

{{-- Profile hero --}}
<div class="card profile-hero-card">
    <div class="profile-banner"></div>
    <div class="profile-hero-body">
        <div class="profile-avatar-xl" id="avatarXl">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}">
            @else
                {{ strtoupper(substr($user->name, 0, 1)) }}
            @endif
        </div>
        <div class="profile-hero-info">
            <h2>{{ $user->name }} <span class="role-chip">{{ $user->role_label }}</span></h2>
            <p>{{ $user->email }}</p>
            <small>Member since {{ $user->created_at->format('F Y') }}</small>
        </div>
        @if ($user->avatar)
            <form method="POST" action="{{ route('profile.avatar.remove') }}" class="remove-avatar-form" data-confirm="Remove your profile photo?">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-danger">Remove photo</button>
            </form>
        @endif
    </div>
</div>

<div class="profile-grid">
    {{-- Profile information --}}
    <div class="card">
        <div class="card-head">
            <span class="card-ic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <div>
                <h3>Profile Information</h3>
                <p>Update your photo, name, and email address.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display: grid; gap: 18px;">
            @csrf
            @method('PUT')

            <div>
                <div class="avatar-upload">
                    <label for="avatar" class="avatar-preview" id="avatarPreview" title="Change photo">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                        <span class="avatar-cam" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </span>
                    </label>
                    <label for="avatar" class="avatar-drop" id="avatarDrop">
                        <span class="avatar-drop-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </span>
                        <span class="avatar-drop-text">
                            <span class="avatar-drop-main"><strong>Click to upload</strong> or drag &amp; drop</span>
                            <span class="upload-hint" id="uploadHint">JPG, PNG or WebP · max 2 MB</span>
                        </span>
                    </label>
                </div>
                <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" hidden>
                @error('avatar') <div class="field-error" style="margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="field-label" for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="card">
        <div class="card-head">
            <span class="card-ic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <div>
                <h3>Update Password</h3>
                <p>Use a long, random password to keep your account secure.</p>
            </div>
        </div>

        @if (session('status_password'))
            <div class="success-box">{{ session('status_password') }}</div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" style="display: grid; gap: 18px;">
            @csrf
            @method('PUT')
            <div>
                <label class="field-label" for="current_password">Current Password <span class="req">*</span></label>
                <input type="password" id="current_password" name="current_password" required>
                @error('current_password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="password">New Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" required>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="password_confirmation">Confirm New Password <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <div>
                <button type="submit" class="btn btn-brand btn-sm">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('avatar');
        var drop = document.getElementById('avatarDrop');
        var preview = document.getElementById('avatarPreview');
        var xl = document.getElementById('avatarXl');
        var hint = document.getElementById('uploadHint');
        var MAX = 2 * 1024 * 1024;

        function setImage(el, url) {
            var img = el.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.alt = '';
                el.insertBefore(img, el.firstChild);
            }
            // remove any initial text node
            Array.prototype.slice.call(el.childNodes).forEach(function (n) {
                if (n.nodeType === 3) el.removeChild(n);
            });
            img.src = url;
        }

        function handle(file) {
            if (!file) return;
            if (!/^image\/(png|jpe?g|webp)$/.test(file.type)) {
                hint.textContent = 'Unsupported file type — use JPG, PNG or WebP';
                drop.classList.add('is-error');
                return;
            }
            if (file.size > MAX) {
                hint.textContent = 'File is too large — max 2 MB';
                drop.classList.add('is-error');
                return;
            }
            drop.classList.remove('is-error');
            var url = URL.createObjectURL(file);
            setImage(preview, url);
            if (xl) setImage(xl, url);
            hint.textContent = file.name;
        }

        input.addEventListener('change', function () { handle(this.files[0]); });

        ['dragenter', 'dragover'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-drag'); });
        });
        ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
            drop.addEventListener(ev, function () { drop.classList.remove('is-drag'); });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            var file = e.dataTransfer.files[0];
            if (!file) return;
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            } catch (err) { /* older browsers: input.files may stay empty */ }
            handle(file);
        });
    })();
</script>
@endsection
