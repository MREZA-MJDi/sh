@if($paginator->hasPages())
    <div class="sh-pagination" role="navigation" aria-label="{{ __('pagination.label') }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
        @if($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <p class="sh-pagination-summary">{{ __('pagination.summary', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
        @endif
        <div class="sh-pagination-controls">
            @if($paginator->onFirstPage())
                <span class="sh-pagination-item sh-pagination-direction" aria-disabled="true"><span class="sh-pagination-arrow" aria-hidden="true"></span>{{ __('pagination.previous') }}</span>
            @else
                <a class="sh-pagination-item sh-pagination-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev"><span class="sh-pagination-arrow" aria-hidden="true"></span>{{ __('pagination.previous') }}</a>
            @endif
            @foreach($elements ?? [] as $element)
                @if(is_string($element))
                    <span class="sh-pagination-gap" aria-hidden="true">{{ $element }}</span>
                @elseif(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page === $paginator->currentPage())
                            <span class="sh-pagination-item" aria-current="page" aria-label="{{ __('pagination.page', ['page' => $page]) }}">{{ $page }}</span>
                        @else
                            <a class="sh-pagination-item" href="{{ $url }}" aria-label="{{ __('pagination.page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if($paginator->hasMorePages())
                <a class="sh-pagination-item sh-pagination-direction sh-pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}<span class="sh-pagination-arrow" aria-hidden="true"></span></a>
            @else
                <span class="sh-pagination-item sh-pagination-direction sh-pagination-next" aria-disabled="true">{{ __('pagination.next') }}<span class="sh-pagination-arrow" aria-hidden="true"></span></span>
            @endif
        </div>
    </div>
@endif
