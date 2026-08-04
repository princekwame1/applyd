@extends('layouts.admin')

@section('title', 'Edit User — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit User</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.users.partials.form', ['model' => $user, 'roles' => $roles])
</div>
@endsection
