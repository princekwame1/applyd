@extends('layouts.app')

@section('title', $post->title.' — Applyd Academy')

@section('content')
<article class="post">
    <header class="post-hero">
        <div class="container">
            <a href="{{ route('blog') }}" class="post-back">← Back to blog</a>
            <div class="post-hero-inner">
                @if ($post->category)
                    <a href="{{ route('blog', ['category' => $post->category->slug]) }}" class="blog-badge post-badge">{{ $post->category->name }}</a>
                @endif
                <h1 class="post-title">{{ $post->title }}</h1>
                <div class="post-meta">
                    <span>{{ $post->author ?? 'Applyd Academy' }}</span>
                    <span>·</span>
                    <span>{{ $post->date_label }}</span>
                    <span>·</span>
                    <span>{{ $post->reading_time }} min read</span>
                </div>
            </div>
        </div>
    </header>

    @if ($post->cover_image_url)
        <div class="container">
            <div class="post-cover">
                <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}">
            </div>
        </div>
    @endif

    <div class="container">
        <div class="post-body job-description">
            {!! $post->body !!}
        </div>

        <div class="post-share">
            <a href="{{ route('blog') }}" class="btn btn-outline btn-sm">← All articles</a>
        </div>
    </div>
</article>

@if ($related->isNotEmpty())
    <section class="alt">
        <div class="container">
            <h2 class="section-title" style="font-size:1.4rem; margin-bottom:24px;">Related articles</h2>
            <div class="blog-grid">
                @foreach ($related as $post)
                    <a class="card blog-card" href="{{ route('blog.show', $post) }}">
                        <div class="blog-thumb">
                            @if ($post->cover_image_url)
                                <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" loading="lazy">
                            @else
                                <div class="blog-thumb-placeholder"></div>
                            @endif
                            @if ($post->category)<span class="blog-badge">{{ $post->category->name }}</span>@endif
                        </div>
                        <div class="blog-body">
                            <div class="blog-meta">
                                <span>{{ $post->date_label }}</span>
                                <span>·</span>
                                <span>{{ $post->reading_time }} min read</span>
                            </div>
                            <h3>{{ $post->title }}</h3>
                            <p class="blog-excerpt">{{ $post->display_excerpt }}</p>
                            <span class="blog-readmore">Read article →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
