@extends('layouts.admin')

@section('title', 'Edit Role — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Role: {{ ucfirst($role->name) }}</h1>

@if (session('error'))
    <div class="error-box" style="max-width:720px;">{{ session('error') }}</div>
@endif

<div class="card" style="max-width: 720px;">
    @include('dashboard.roles.partials.form', ['model' => $role, 'permissions' => $permissions])
</div>
@endsection
