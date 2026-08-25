@extends('layouts.app')

@section('title', ($activeCategory ? $activeCategory->name.' — ' : '').'Blog — Applyd Academy')
@section('og_title', 'The Applyd Blog')
@section('og_description', 'Practical marketing insights, tool guides, and career advice from our team.')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">Insights</span>
        <h1 class="section-title">The Applyd Blog</h1>
        <p class="section-lead">Practical marketing insights, tool guides, and career advice from our team.</p>
    </div>
</section>

<section>
    <div class="container">
        @if ($categories->isNotEmpty())
            <div class="blog-tabs">
                <a href="{{ route('blog') }}" class="blog-tab {{ ! $activeCategory ? 'active' : '' }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route('blog', ['category' => $category->slug]) }}"
                       class="blog-tab {{ $activeCategory && $activeCategory->id === $category->id ? 'active' : '' }}">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <div class="card center" style="padding:48px;">
                <h3 style="margin-bottom:8px;">No posts yet</h3>
                <p style="color:var(--ink-soft);">Check back soon for fresh insights.</p>
            </div>
        @else
            <div class="blog-grid">
                @foreach ($posts as $post)
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

            @if ($posts->hasPages())
                <div class="jobs-pagination" style="margin-top:44px;">
                    {{ $posts->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
