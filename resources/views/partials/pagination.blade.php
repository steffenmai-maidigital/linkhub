@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Seitennavigation') }}">
        @if ($paginator->onFirstPage())
            <span class="pager-btn disabled" aria-hidden="true">‹ {{ __('Zurück') }}</span>
        @else
            <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ {{ __('Zurück') }}</a>
        @endif

        @if(method_exists($paginator, 'lastPage'))
            <span class="pager-info">{{ __('Seite :a von :b', ['a' => $paginator->currentPage(), 'b' => $paginator->lastPage()]) }}
                <span class="hint">({{ number_format($paginator->total(), 0, ',', '.') }} {{ __('Einträge') }})</span></span>
        @else
            <span class="pager-info">{{ __('Seite') }} {{ $paginator->currentPage() }}</span>
        @endif

        @if ($paginator->hasMorePages())
            <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Weiter') }} ›</a>
        @else
            <span class="pager-btn disabled" aria-hidden="true">{{ __('Weiter') }} ›</span>
        @endif
    </nav>
@endif
