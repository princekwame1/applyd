{{-- simplePaginate()'s two-button form. Same reasoning as applyd.blade.php. --}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">&lsaquo; Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Prev</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rsaquo;</a>
        @else
            <span class="is-disabled" aria-disabled="true">Next &rsaquo;</span>
        @endif
    </nav>
@endif
