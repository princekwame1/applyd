@extends('layouts.admin')

@section('title', 'Edit Video — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Video</h1>

<div class="card" style="max-width: 720px;">
    @include('dashboard.videos.partials.form', ['model' => $video])
</div>
@endsection
