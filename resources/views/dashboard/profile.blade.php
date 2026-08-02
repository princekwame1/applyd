@extends('layouts.admin')

@section('title', 'Profile — Applyd Academy')

@section('content')
@php($user = auth()->user())

<h1 class="section-title" style="margin-bottom: 24px;">Profile Settings</h1>

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
        <h3 style="margin-bottom: 4px;">Profile Information</h3>
        <p style="color: var(--ink-soft); font-size: .92rem; margin-bottom: 18px;">Update your photo, name, and email address.</p>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display: grid; gap: 18px;">
            @csrf
            @method('PUT')

            <div class="avatar-upload">
                <div class="avatar-preview" id="avatarPreview">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <label for="avatar" class="btn btn-outline btn-sm" style="cursor: pointer;">Choose Photo</label>
                    <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" hidden>
                    <div class="upload-hint" id="uploadHint">JPG, PNG or WebP · max 2 MB</div>
                    @error('avatar') <div class="field-error">{{ $message }}</div> @enderror
                </div>
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
        <h3 style="margin-bottom: 4px;">Update Password</h3>
        <p style="color: var(--ink-soft); font-size: .92rem; margin-bottom: 18px;">Use a long, random password to keep your account secure.</p>

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
    document.getElementById('avatar').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var url = URL.createObjectURL(file);
        ['avatarPreview', 'avatarXl'].forEach(function (id) {
            document.getElementById(id).innerHTML = '<img src="' + url + '" alt="">';
        });
        document.getElementById('uploadHint').textContent = file.name;
    });
</script>
@endsection
