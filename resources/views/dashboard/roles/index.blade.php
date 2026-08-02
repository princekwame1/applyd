@extends('layouts.admin')

@section('title', 'Roles & Permissions — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Roles &amp; Permissions</h1>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="roles-grid">
    <div class="card">
        <h3 style="margin-bottom: 14px;">Create Role</h3>
        <form method="POST" action="{{ route('dashboard.roles.store') }}" style="display:grid; gap:16px;">
            @csrf
            <div>
                <label class="field-label" for="name">Role Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" placeholder="e.g. moderator" value="{{ old('name') }}" required>
                @error('name') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label">Permissions</label>
                <div class="perm-options">
                    @foreach ($permissions as $permission)
                        <label class="chk">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', [])))>
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
                @error('permissions') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <button type="submit" class="btn btn-brand btn-sm">Create Role</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 14px;">Permissions</h3>
        <form method="POST" action="{{ route('dashboard.permissions.store') }}" style="display:flex; gap:10px; margin-bottom: 16px;">
            @csrf
            <input type="text" name="name" placeholder="e.g. manage reports" required>
            <button type="submit" class="btn btn-brand btn-sm" style="white-space:nowrap;">Add</button>
        </form>
        @error('name') <div class="field-error" style="margin-bottom:10px;">{{ $message }}</div> @enderror
        <div class="perm-list">
            @forelse ($permissions as $permission)
                <span class="perm-chip">
                    {{ $permission->name }}
                    <form method="POST" action="{{ route('dashboard.permissions.destroy', $permission) }}" data-confirm="Delete the '{{ $permission->name }}' permission? It will be removed from all roles.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" aria-label="Delete permission">×</button>
                    </form>
                </span>
            @empty
                <p style="color:var(--ink-soft); font-size:.9rem;">No permissions yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <livewire:roles-table />
</div>
@endsection
