{{--
    Paginator view for admin lists (used through <x-admin.pager>). Always shows how many there are;
    the page links appear only when there is more than one page. Needs a length-aware paginator.
--}}
@php
    $noun = $noun ?? 'result';
    $total = $paginator->total();
    $words = \Illuminate\Support\Str::plural($noun, $total);
@endphp
@if ($total > 0)
    <nav class="pager" aria-label="Pagination">
        <p class="pager-info">
            @if ($paginator->hasPages())
                Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($total) }} {{ $words }}
            @else
                {{ number_format($total) }} {{ $words }}
            @endif
        </p>

        @if ($paginator->hasPages())
            <div class="pager-links">
                @if ($paginator->onFirstPage())
                    <span class="pager-btn is-disabled" aria-disabled="true"><x-admin.icon name="chevron-left" /><span class="sr-only">Previous page</span></span>
                @else
                    <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" data-skeleton="rows"><x-admin.icon name="chevron-left" /></a>
                @endif

                {{-- Phones: the numbers are too wide, so show where you are instead. --}}
                <span class="pager-page">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

                <span class="pager-numbers">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="pager-gap" aria-hidden="true">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="pager-btn is-current" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="pager-btn" href="{{ $url }}" aria-label="Page {{ $page }}" data-skeleton="rows">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </span>

                @if ($paginator->hasMorePages())
                    <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" data-skeleton="rows"><x-admin.icon name="chevron-right" /></a>
                @else
                    <span class="pager-btn is-disabled" aria-disabled="true"><x-admin.icon name="chevron-right" /><span class="sr-only">Next page</span></span>
                @endif
            </div>
        @endif
    </nav>
@endif
