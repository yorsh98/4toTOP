@php
    $columnas = [
        'rit' => 'Causa',
        'nombre_partes' => 'Partes',
        'materia' => 'Materia',
        'fecha_decision' => 'Decisión',
        'juez' => 'Juez',
        'instancia' => 'Instancia',
    ];
    $ariaSort = fn (string $campo) => $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<div class="fo-sentencias">
    {{-- Barra de búsqueda y filtros --}}
    <div class="fo-listbar fo-listbar--stack">
        <label class="fo-listbar-field fo-listbar-field--lg cm-focus-ring">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar sentencias</span>
            <input type="search" wire:model.live.debounce.250ms="search" autocomplete="off"
                   placeholder="RIT-año, RUC, partes o juez" aria-controls="fo-sentencias-resultados" aria-describedby="fo-sentencias-hint">
            <span class="fo-listbar-loading cm-shimmer" wire:loading wire:target="search,filtroInstancia,perPage,sortBy,gotoPage,nextPage,previousPage">Buscando…</span>
        </label>
        <p class="fo-hint fo-sentencias-hint" id="fo-sentencias-hint">Para una causa exacta escribe el RIT y el año, por ejemplo <span class="fo-num">136-2025</span>.</p>

        <div class="fo-sentencias-filters">
            @if ($instancias->isNotEmpty())
                <div class="fo-chips" role="group" aria-label="Instancia">
                    <button type="button" class="fo-chip cm-press" wire:click="$set('filtroInstancia', '')" aria-pressed="{{ $filtroInstancia === '' ? 'true' : 'false' }}">Todas</button>
                    @foreach ($instancias as $instancia)
                        <button type="button" class="fo-chip cm-press" wire:key="inst-{{ $loop->index }}"
                                wire:click="$set('filtroInstancia', @js($instancia))"
                                aria-pressed="{{ $filtroInstancia === $instancia ? 'true' : 'false' }}">{{ $instancia }}</button>
                    @endforeach
                </div>
            @endif
            <label class="fo-perpage">
                <span>Mostrar</span>
                <select wire:model.live="perPage" class="fo-select fo-select--sm">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <p class="fo-listbar-count" aria-live="polite">
            <b class="fo-num">{{ number_format($sentencias->total(), 0, ',', '.') }}</b>
            {{ $sentencias->total() === 1 ? 'sentencia' : 'sentencias' }}
            @if ($sentencias->total() > 0)
                · mostrando {{ $sentencias->firstItem() }}–{{ $sentencias->lastItem() }}
            @endif
        </p>
    </div>

    {{-- Resultados: tabla en escritorio, tarjetas en móvil --}}
    <div class="fo-table-wrap" id="fo-sentencias-resultados" wire:loading.class="is-loading" wire:target="search,filtroInstancia,perPage,sortBy,gotoPage,nextPage,previousPage">
        <table class="fo-table">
            <caption class="sr-only">Índice de sentencias del 4º TOP</caption>
            <thead>
                <tr>
                    @foreach ($columnas as $campo => $etiqueta)
                        <th scope="col" aria-sort="{{ $ariaSort($campo) }}">
                            <button type="button" class="fo-sort" wire:click="sortBy('{{ $campo }}')">
                                {{ $etiqueta }}
                                <i class="fa-solid {{ $sortField === $campo ? ($sortDirection === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down') : 'fa-sort' }}" aria-hidden="true"></i>
                            </button>
                        </th>
                    @endforeach
                    <th scope="col"><span class="sr-only">Detalle</span></th>
                </tr>
            </thead>

            @forelse ($sentencias as $s)
                <tbody x-data="{ open: false }" wire:key="sent-{{ $s->id }}" class="fo-table-group">
                    <tr>
                        <td data-label="Causa">
                            <span class="fo-num fo-causa">{{ $s->rit }}-{{ $s->ano }}</span>
                            <span class="fo-num fo-cell-sub fo-ruc">RUC {{ $s->ruc }}</span>
                        </td>
                        <td data-label="Partes"><span class="fo-clamp">{{ $s->nombre_partes }}</span></td>
                        <td data-label="Materia">{{ $s->materia }}</td>
                        <td data-label="Decisión">
                            <span class="fo-num">{{ optional($s->fecha_decision)->format('d-m-Y') }}</span>
                            <span class="fo-cell-sub fo-clamp">{{ $s->glosa_decision }}</span>
                        </td>
                        <td data-label="Juez">{{ $s->juez }}</td>
                        <td data-label="Instancia">{{ $s->instancia }}</td>
                        <td class="fo-table-toggle">
                            <button type="button" class="fo-icon-btn cm-press" x-on:click="open = ! open" x-bind:aria-expanded="open"
                                    aria-controls="sent-det-{{ $s->id }}" aria-label="Ver detalle de la causa {{ $s->rit }}-{{ $s->ano }}">
                                <i class="fa-solid fa-chevron-down" x-bind:class="open && 'fa-rotate-180'" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="fo-table-detail" id="sent-det-{{ $s->id }}" x-show="open" x-cloak>
                        <td colspan="7">
                            <dl class="fo-detail">
                                <div><dt>Partes</dt><dd>{{ $s->nombre_partes }}</dd></div>
                                <div><dt>Decisión</dt><dd>{{ $s->glosa_decision }}</dd></div>
                                {{-- En móvil estas columnas no caben en la tarjeta: se leen aquí --}}
                                <div class="fo-detail-mobile"><dt>Materia</dt><dd>{{ $s->materia }}</dd></div>
                                <div class="fo-detail-mobile"><dt>Juez</dt><dd>{{ $s->juez }}</dd></div>
                                <div class="fo-detail-mobile"><dt>Instancia</dt><dd>{{ $s->instancia }}</dd></div>
                            </dl>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="7" class="fo-table-empty">
                            @if ($search !== '' || $filtroInstancia !== '')
                                Ninguna sentencia coincide con la búsqueda. Revisa el RIT-año o
                                <button type="button" class="fo-link" x-on:click="$wire.set('search', ''); $wire.set('filtroInstancia', '')">limpia los filtros</button>.
                            @else
                                Aún no hay sentencias cargadas. Se importan desde el backoffice, en Documentos.
                            @endif
                        </td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>

    {{ $sentencias->links('components.public.pagination') }}
</div>
