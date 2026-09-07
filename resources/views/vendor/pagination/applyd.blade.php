{{--
    The site's own pagination markup.

    Laravel's bundled views are written for Tailwind or Bootstrap, and public
    pages load neither — only public/css/app.css. Under the Tailwind view the
    utility classes are inert, so the "hidden on mobile" block never hides and
    the chevron SVGs, having no w-5 h-5 to constrain them, expand to fill the
    page. Hence plain markup and text arrows: nothing here depends on a
    stylesheet that isn't served. Styling is the .pagination block in app.css.
--}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">&lsaquo; Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo; Prev</a>
        @endif

        @foreach ($elements as $element)
            {{-- A run of numbers the paginator has skipped over. --}}
            @if (is_string($element))
                <span class="is-gap" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next &rsaquo;</a>
        @else
            <span class="is-disabled" aria-disabled="true">Next &rsaquo;</span>
        @endif
    </nav>
@endif
