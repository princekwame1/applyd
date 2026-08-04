@extends('layouts.admin')

@section('title', 'Edit Schedule — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Schedule Entry</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.schedules.partials.form', ['model' => $schedule])
</div>
@endsection
