@extends('layouts.admin')

@section('title', 'Edit User — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit User</h1>

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('dashboard.users.update', $user) }}" style="display:grid; gap:16px;">
        @csrf
        @method('PUT')
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
            <label class="field-label" for="role">Role <span class="req">*</span></label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', $user->getRoleNames()->first()) === $role->name)>{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            @error('role') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="password">New Password <small>(leave empty to keep current)</small></label>
            <input type="password" id="password" name="password">
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation">
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.users') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
