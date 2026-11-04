{{-- Paginación de Livewire para el front office (usar con $paginator->links('components.public.pagination')) --}}
@if ($paginator->hasPages())
    @php($pageName = $paginator->getPageName())
    <nav data-cm-pager class="fo-pager" aria-label="Páginas">
        <button type="button" class="fo-pager-btn cm-press" data-cm-page="prev" wire:click="previousPage('{{ $pageName }}')"
                @disabled($paginator->onFirstPage()) aria-label="Página anterior">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="fo-pager-gap" aria-hidden="true">…</span>
            @else
                @foreach ($element as $page => $url)
                    <button type="button" wire:key="page-{{ $page }}" data-cm-page="{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                            @class(['fo-pager-btn cm-press fo-num', 'is-current' => $page === $paginator->currentPage()])
                            @if ($page === $paginator->currentPage()) aria-current="page" @endif
                            aria-label="Página {{ $page }}">{{ $page }}</button>
                @endforeach
            @endif
        @endforeach

        <button type="button" class="fo-pager-btn cm-press" data-cm-page="next" wire:click="nextPage('{{ $pageName }}')"
                @disabled(! $paginator->hasMorePages()) aria-label="Página siguiente">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </nav>
@endif
