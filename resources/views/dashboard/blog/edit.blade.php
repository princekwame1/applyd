@extends('layouts.admin')

@section('title', 'Edit Post — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Edit Post</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.blog') }}">Back to Blog</a>
</div>

<div class="card" style="max-width: 760px;">
    @include('dashboard.blog.partials.form', ['model' => $post, 'categories' => $categories])
</div>
@include('partials.quill')
@endsection
