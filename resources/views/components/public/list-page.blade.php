@props([
    'title',
    'lead' => null,
    'meta' => null,
    'total' => 0,
    'noun' => ['ítem', 'ítems'],
    'filter' => true,
    'placeholder' => 'Filtrar…',
    'empty' => 'Aún no hay nada publicado aquí.',
])

{{-- Página de listado del front office. Emite tres bloques hermanos (cabecera, barra y
     lista) para que la transición "abrir app" los haga entrar escalonados. --}}
<header class="fo-page-head fo-list-head">
    <a href="{{ route('welcome') }}" class="fo-back" data-fo-link>
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inicio
    </a>
    <h1 class="fo-page-title">{{ $title }}</h1>
    @if ($lead)
        <p class="fo-page-lead">{{ $lead }}</p>
    @endif
    @if ($meta)
        <p class="fo-list-meta">{{ $meta }}</p>
    @endif
</header>

@if ($filter && $total > 0)
    <div class="fo-listbar" data-fo-filter-bar data-noun-one="{{ $noun[0] }}" data-noun-many="{{ $noun[1] }}">
        <label class="fo-listbar-field cm-focus-ring">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">{{ $placeholder }}</span>
            <input type="search" placeholder="{{ $placeholder }}" autocomplete="off" data-fo-filter
                   aria-controls="fo-list-body" aria-keyshortcuts="/">
            <kbd class="fo-kbd" aria-hidden="true">/</kbd>
        </label>
        <p class="fo-listbar-count" aria-live="polite">
            <b data-fo-filter-count>{{ $total }}</b> <span data-fo-filter-of hidden>de {{ $total }}</span>
            <span data-fo-filter-noun>{{ $total === 1 ? $noun[0] : $noun[1] }}</span>
        </p>
        @isset($toolbar)
            <div class="fo-listbar-extra">{{ $toolbar }}</div>
        @endisset
    </div>
@endif

<div class="fo-list-body" id="fo-list-body" data-fo-filter-scope>
    @if ($total > 0)
        {{ $slot }}
        <div class="fo-list-empty" data-fo-filter-empty hidden>
            <p>Nada coincide con «<span data-fo-filter-query></span>».</p>
            <button type="button" class="fo-btn fo-btn--ghost cm-press" data-fo-filter-clear>Limpiar filtro</button>
        </div>
    @else
        <div class="fo-list-empty">
            <p>{{ $empty }}</p>
        </div>
    @endif
</div>
