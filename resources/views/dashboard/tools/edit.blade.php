@extends('layouts.admin')

@section('title', 'Edit Tool — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Tool</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.tools.partials.form', ['model' => $tool])
</div>
@endsection
