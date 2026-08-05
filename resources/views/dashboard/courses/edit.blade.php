@extends('layouts.admin')

@section('title', 'Edit Course — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Course</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.courses.partials.form', ['model' => $course])
</div>
@include('partials.quill')
@endsection
