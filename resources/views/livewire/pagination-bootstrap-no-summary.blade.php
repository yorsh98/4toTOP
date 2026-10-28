@php
    $scrollIntoViewJsSnippet = $scrollTo !== false
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
        : '';
@endphp

@if ($paginator->hasPages())
    <nav class="bo-pagination" aria-label="Paginación">
        <ul class="bo-pagination-list">
            @if ($paginator->onFirstPage())
                <li class="bo-pagination-disabled" aria-disabled="true"><span class="bo-pagination-link" aria-label="Página anterior">&lsaquo;</span></li>
            @else
                <li class="bo-pagination-item"><button type="button" class="bo-pagination-link" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Página anterior">&lsaquo;</button></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="bo-pagination-disabled" aria-disabled="true"><span class="bo-pagination-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <li class="bo-pagination-active" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}" aria-current="page"><span class="bo-pagination-link">{{ $page }}</span></li>
                        @else
                            <li class="bo-pagination-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"><button type="button" class="bo-pagination-link" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}">{{ $page }}</button></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="bo-pagination-item"><button type="button" class="bo-pagination-link" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Página siguiente">&rsaquo;</button></li>
            @else
                <li class="bo-pagination-disabled" aria-disabled="true"><span class="bo-pagination-link" aria-label="Página siguiente">&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
