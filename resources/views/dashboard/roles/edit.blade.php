@extends('layouts.admin')

@section('title', 'Edit Role — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Role: {{ ucfirst($role->name) }}</h1>

@if (session('error'))
    <div class="error-box" style="max-width:720px;">{{ session('error') }}</div>
@endif

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('dashboard.roles.update', $role) }}" style="display:grid; gap:16px;">
        @csrf
        @method('PUT')
        <div>
            <label class="field-label" for="name">Role Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" {{ $role->name === 'super' ? 'readonly' : '' }} required>
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label">Permissions</label>
            @if ($role->name === 'super')
                <p style="color:var(--ink-soft); font-size:.9rem;">The super role always has every permission.</p>
            @endif
            <div class="perm-options">
                @foreach ($permissions as $permission)
                    <label class="chk">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                            @checked(in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all())))
                            @disabled($role->name === 'super')>
                        {{ $permission->name }}
                    </label>
                @endforeach
            </div>
            @error('permissions') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-brand btn-sm">Save Changes</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.roles') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
