@extends('layouts.admin')

@section('title', 'Users — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Users</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.users.export') }}">Export Excel</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Create User</h3>
    <form method="POST" action="{{ route('dashboard.users.store') }}" class="schedule-form">
        @csrf
        <div>
            <label class="field-label" for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="role">Role <span class="req">*</span></label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', 'student') === $role->name)>{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            @error('role') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="password">Password <span class="req">*</span></label>
            <input type="password" id="password" name="password" required>
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="password_confirmation">Confirm Password <span class="req">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <div class="submit-cell">
            <button type="submit" class="btn btn-brand btn-sm">Create User</button>
        </div>
    </form>
</div>

<div class="card">
    <livewire:users-table />
</div>
@endsection
