@extends('layouts.admin')

@section('title', 'Edit Facilitator — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Facilitator</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.facilitators.partials.form', ['model' => $facilitator])
</div>
@endsection
