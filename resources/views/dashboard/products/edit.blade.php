@extends('layouts.admin')

@section('title', 'Edit Product — Applyd Academy')

@section('content')
<h1 class="section-title" style="margin-bottom: 24px;">Edit Product</h1>

@include('partials.quill')

<div class="card" style="max-width: 780px;">
    @include('dashboard.products.partials.form', ['model' => $product])
</div>
@endsection
