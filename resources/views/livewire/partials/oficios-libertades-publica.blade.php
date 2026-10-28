<div class="fo-registros">
    @php
        $columnas = ['Numentregado' => 'N° entregado', 'año' => 'Año', 'CausaAsig' => 'Causa asignada', 'UserSolicitante' => 'Solicitante', 'UserDirigido' => 'Motivo / dirigido', 'updated_at' => 'Fecha utilización'];
        $ariaSort = fn ($campo) => $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
    @endphp
    <div class="fo-listbar fo-listbar--stack">
        <label class="fo-listbar-field fo-listbar-field--lg cm-focus-ring">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar {{ $tipo }}</span>
            <input type="search" wire:model.live.debounce.200ms="search" placeholder="Número, causa o solicitante" aria-controls="fo-registros-resultados">
        </label>
        <div class="fo-registros-filtros">
            <label class="fo-perpage"><span>Año</span>
                <select class="fo-select fo-select--sm" wire:model.live.debounce.100ms="filtroAnio">
                    <option value="">Todos los años</option>
                    @foreach ($anios as $anio)<option value="{{ $anio }}">{{ $anio }}</option>@endforeach
                </select>
            </label>
            <label class="fo-perpage"><span>Mostrar</span>
                <select class="fo-select fo-select--sm" wire:model.live="perPage">
                    @foreach ([10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                </select>
            </label>
        </div>
        <p class="fo-listbar-count" aria-live="polite"><b class="fo-num">{{ $registros->total() }}</b> {{ $registros->total() === 1 ? 'registro' : 'registros' }}</p>
    </div>
    <div class="fo-table-wrap fo-table-wrap--glass fo-registros-table" id="fo-registros-resultados">
        <table class="fo-table">
            <caption class="sr-only">Últimos {{ $tipo }}</caption>
            <thead><tr>
                @foreach ($columnas as $campo => $etiqueta)
                    <th scope="col" aria-sort="{{ $ariaSort($campo) }}"><button type="button" class="fo-sort" wire:click="sortBy('{{ $campo }}')">{{ $etiqueta }} <i class="fa-solid {{ $sortField === $campo ? ($sortDirection === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down') : 'fa-sort' }}" aria-hidden="true"></i></button></th>
                @endforeach
            </tr></thead>
            @forelse ($registros as $registro)
                <tbody class="fo-table-group" wire:key="registro-{{ $registro->id }}"><tr>
                    <td data-label="N° entregado"><strong class="fo-num">{{ $registro->Numentregado }}-{{ $registro->año }}</strong></td>
                    <td data-label="Año"><span class="fo-num">{{ $registro->año }}</span></td>
                    @foreach (['CausaAsig' => 'Causa asignada', 'UserSolicitante' => 'Solicitante', 'UserDirigido' => 'Motivo / dirigido'] as $campo => $etiqueta)
                        <td data-label="{{ $etiqueta }}">@if (filled($registro->$campo) && $registro->$campo !== '-'){{ $registro->$campo }}@else<span class="fo-dash">—</span>@endif</td>
                    @endforeach
                    <td data-label="Fecha utilización">@if ($registro->updated_at ?? $registro->created_at)<span class="fo-num">{{ ($registro->updated_at ?? $registro->created_at)->format('d-m-Y H:i') }}</span>@else<span class="fo-dash">—</span>@endif</td>
                </tr></tbody>
            @empty
                <tbody><tr><td colspan="6" class="fo-table-empty">No se encontraron registros. Prueba con otra búsqueda o cambia el año.</td></tr></tbody>
            @endforelse
        </table>
    </div>
    {{ $registros->links('components.public.pagination') }}
</div>
