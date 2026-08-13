{{-- Session video cards. Expects $videos (a collection of SessionVideo). --}}
<div class="video-grid">
    @foreach ($videos as $video)
        <article class="card video-card">
            {{-- Facade, not an iframe: nothing loads from YouTube until the
                 viewer clicks, and without JS the link just opens YouTube. --}}
            <a class="video-thumb" href="{{ $video->watch_url }}" target="_blank" rel="noopener"
               data-video-embed="{{ $video->embed_url }}" data-video-title="{{ $video->title }}"
               aria-label="Play: {{ $video->title }}">
                <img src="{{ $video->thumbnail_url }}" alt="" loading="lazy">
                <span class="video-play" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="8 5 19 12 8 19"/></svg>
                </span>
                @if ($video->session_label)
                    <span class="video-chip">{{ $video->session_label }}</span>
                @endif
            </a>
            <div class="video-body">
                @if ($video->date_label)
                    <div class="video-meta">{{ $video->date_label }}</div>
                @endif
                <h3>{{ $video->title }}</h3>
                @if ($video->description)
                    <p class="video-desc">{{ $video->short_description }}</p>
                @endif
                <a class="video-link" href="{{ $video->watch_url }}" target="_blank" rel="noopener">Watch on YouTube →</a>
            </div>
        </article>
    @endforeach
</div>

@once
@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-video-embed]');
        if (!trigger) return;

        e.preventDefault();

        var frame = document.createElement('iframe');
        frame.className = 'video-frame';
        frame.src = trigger.getAttribute('data-video-embed');
        frame.title = trigger.getAttribute('data-video-title') || 'Session video';
        frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        frame.allowFullscreen = true;
        trigger.replaceWith(frame);
    });
</script>
@endpush
@endonce
