@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav data-cm-pager class="bo-pagination" aria-label="Paginación">
            <div class="bo-pagination-mobile">
                <ul class="bo-pagination-list">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="bo-pagination-disabled" aria-disabled="true">
                            <span class="bo-pagination-link">@lang('pagination.previous')</span>
                        </li>
                    @else
                        <li class="bo-pagination-item">
                            <button type="button" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="bo-pagination-link" data-cm-page="prev" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Página anterior">@lang('pagination.previous')</button>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="bo-pagination-item">
                            <button type="button" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="bo-pagination-link" data-cm-page="next" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Página siguiente">@lang('pagination.next')</button>
                        </li>
                    @else
                        <li class="bo-pagination-disabled" aria-disabled="true">
                            <span class="bo-pagination-link" aria-hidden="true">@lang('pagination.next')</span>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="bo-pagination-desktop">
                <div>
                    <p class="bo-muted">
                        {!! __('Mostrando') !!}
                        <span class="bo-strong">{{ $paginator->firstItem() }}</span>
                        {!! __('a ') !!}
                        <span class="bo-strong">{{ $paginator->lastItem() }}</span>
                        {!! __('de') !!}
                        <span class="bo-strong">{{ $paginator->total() }}</span>
                        {!! __('registros') !!}
                    </p>
                </div>

                <div>
                    <ul class="bo-pagination-list">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <li class="bo-pagination-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                                <span class="bo-pagination-link" aria-label="Página anterior">&lsaquo;</span>
                            </li>
                        @else
                            <li class="bo-pagination-item">
                                <button type="button" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="bo-pagination-link" data-cm-page="prev" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="@lang('pagination.previous')">&lsaquo;</button>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <li class="bo-pagination-disabled" aria-disabled="true"><span class="bo-pagination-link">{{ $element }}</span></li>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <li class="bo-pagination-active" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}" aria-current="page"><span class="bo-pagination-link">{{ $page }}</span></li>
                                    @else
                                        <li class="bo-pagination-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"><button type="button" class="bo-pagination-link" data-cm-page="{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}">{{ $page }}</button></li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li class="bo-pagination-item">
                                <button type="button" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="bo-pagination-link" data-cm-page="next" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="@lang('pagination.next')">&rsaquo;</button>
                            </li>
                        @else
                            <li class="bo-pagination-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                                <span class="bo-pagination-link" aria-label="Página siguiente">&rsaquo;</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    @endif
</div>
