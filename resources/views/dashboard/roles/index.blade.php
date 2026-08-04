@extends('layouts.admin')

@section('title', 'Roles & Permissions — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Roles &amp; Permissions</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#roleCreateTpl" data-modal-title="Create Role">Create Role</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-open data-modal-template="#permissionCreateTpl" data-modal-title="Add Permission">Add Permission</button>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Permissions</h3>
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

<div class="card">
    <livewire:roles-table />
</div>

<template id="roleCreateTpl">
    @include('dashboard.roles.partials.form', ['model' => null, 'permissions' => $permissions])
</template>
<template id="permissionCreateTpl">
    @include('dashboard.roles.partials.permission-form')
</template>
@endsection
