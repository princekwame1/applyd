@extends('layouts.admin')

@section('title', 'Users — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Users</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#userCreateTpl" data-modal-title="Create User">Create User</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.users.export') }}">Export Excel</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card">
    <livewire:users-table />
</div>

<template id="userCreateTpl">
    @include('dashboard.users.partials.form', ['model' => null, 'roles' => $roles])
</template>
@endsection
