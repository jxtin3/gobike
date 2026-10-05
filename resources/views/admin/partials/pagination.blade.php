@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">
        <p class="pager-info">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <div class="pager-links">
            @if ($paginator->onFirstPage())
                <span class="pager-btn is-disabled" aria-disabled="true"><x-admin.icon name="chevron-left" /></span>
            @else
                <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><x-admin.icon name="chevron-left" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-gap">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-btn is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager-btn" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><x-admin.icon name="chevron-right" /></a>
            @else
                <span class="pager-btn is-disabled" aria-disabled="true"><x-admin.icon name="chevron-right" /></span>
            @endif
        </div>
    </nav>
@endif