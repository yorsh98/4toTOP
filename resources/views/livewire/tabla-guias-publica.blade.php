<div class="fo-guias">
    @php
        $columnas = [
            'nombre_completo' => 'Nombre', 'institucion' => 'Institución',
            'telefono1' => 'Teléfono 1', 'telefono2' => 'Teléfono 2',
            'email' => 'Email', 'rut' => 'RUT',
        ];
        $ariaSort = fn (string $campo) => $sortField === $campo ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
    @endphp

    <div class="fo-listbar fo-listbar--stack">
        <label class="fo-listbar-field fo-listbar-field--lg cm-focus-ring">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar guías</span>
            <input type="search" wire:model.live.debounce.100ms="search" placeholder="Nombre, teléfono o RUT" aria-controls="fo-guias-resultados">
        </label>
        <div class="fo-guias-filters">
            <label class="fo-perpage">
                <span>Institución</span>
                <select class="fo-select fo-select--sm" wire:model.live.debounce.50ms="filtroInstitucion">
                    <option value="">Todas</option>
                    @foreach ($instituciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label class="fo-perpage">
                <span>Mostrar</span>
                <select class="fo-select fo-select--sm" wire:model.live="perPage">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p class="fo-listbar-count" aria-live="polite"><b class="fo-num">{{ $guias->total() }}</b> {{ $guias->total() === 1 ? 'contacto' : 'contactos' }}</p>
    </div>

    <div class="fo-table-wrap fo-table-wrap--glass fo-guias-table" id="fo-guias-resultados">
        <table class="fo-table">
            <caption class="sr-only">Guías telefónicas</caption>
            <thead><tr>
                @foreach ($columnas as $campo => $etiqueta)
                    <th scope="col" aria-sort="{{ $ariaSort($campo) }}">
                        <button type="button" class="fo-sort" wire:click="sortBy('{{ $campo }}')">
                            {{ $etiqueta }}
                            <i class="fa-solid {{ $sortField === $campo ? ($sortDirection === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down') : 'fa-sort' }}" aria-hidden="true"></i>
                        </button>
                    </th>
                @endforeach
            </tr></thead>
            @forelse ($guias as $guia)
                <tbody class="fo-table-group" wire:key="guia-{{ $guia->id }}"><tr>
                    <td data-label="Nombre"><strong>{{ $guia->nombre_completo }}</strong></td>
                    <td data-label="Institución">@if (isset($instituciones[$guia->institucion])){{ $instituciones[$guia->institucion] }}@else<span class="fo-dash">—</span>@endif</td>
                    @foreach (['telefono1' => 'Teléfono 1', 'telefono2' => 'Teléfono 2'] as $campo => $etiqueta)
                        <td data-label="{{ $etiqueta }}">
                            @if (filled($guia->$campo))
                                <a href="tel:{{ preg_replace('/\s+/', '', $guia->$campo) }}" class="fo-num">{{ $guia->$campo }}</a>
                            @else
                                <span class="fo-dash">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td data-label="Email">
                        @if (filled($guia->email))
                            <a href="mailto:{{ $guia->email }}">{{ $guia->email }}</a>
                        @else
                            <span class="fo-dash">—</span>
                        @endif
                    </td>
                    <td data-label="RUT">
                        @if (filled($guia->rut))
                            <span class="fo-num fo-guias-rut">{{ $guia->rut }}</span>
                        @else
                            <span class="fo-dash">—</span>
                        @endif
                    </td>
                </tr></tbody>
            @empty
                <tbody><tr><td colspan="6" class="fo-table-empty">
                    @if ($search !== '' || $filtroInstitucion !== '')
                        Ningún contacto coincide con la búsqueda o el filtro. <button type="button" class="fo-link" x-on:click="$wire.set('search', ''); $wire.set('filtroInstitucion', '')">Limpiar filtros</button>.
                    @else
                        Aún no hay guías cargadas. Se agregan desde el backoffice, en Guías.
                    @endif
                </td></tr></tbody>
            @endforelse
        </table>
    </div>
    {{ $guias->links('components.public.pagination') }}
</div>
