<div class="fo-bitacora">
    <x-public.page-head title="Bitácora de pruebas" lead="Consulta las pruebas registradas por juicio y día." />

    <div class="fo-bitacora-grid">
        <section class="fo-bitacora-panel" aria-labelledby="bitacora-juicios">
            <h2 id="bitacora-juicios">Juicios</h2>
            <label class="fo-listbar-field cm-focus-ring">
                <span class="sr-only">Buscar RIT</span>
                <input type="search" wire:model.live.debounce.300ms="searchRit" placeholder="Buscar RIT">
            </label>
            <div class="fo-bitacora-list">
                @forelse ($rits as $rit)
                    <div class="fo-bitacora-row" wire:key="rit-{{ $rit }}">
                        <button type="button" class="fo-bitacora-choice fo-num" wire:click="selectRit(@js($rit))" @if (trim($selectedRit) === trim($rit)) aria-current="true" @endif>{{ $rit }}</button>
                        @if (!empty($enlacesPorRit[$rit]))
                            <a class="fo-bitacora-link" href="{{ $enlacesPorRit[$rit] }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir las pruebas del juicio {{ $rit }} en una pestaña nueva"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> Ver pruebas <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        @endif
                    </div>
                @empty
                    <p class="fo-bitacora-empty">No hay juicios que mostrar. Prueba con otro RIT o consulta más tarde.</p>
                @endforelse
            </div>
        </section>

        <section class="fo-bitacora-panel" aria-labelledby="bitacora-dias">
            <h2 id="bitacora-dias">Días del juicio</h2>
            <label class="fo-listbar-field cm-focus-ring">
                <span class="sr-only">Buscar día o fecha</span>
                <input type="search" wire:model.live.debounce.300ms="searchDia" placeholder="Buscar día o fecha">
            </label>
            <div class="fo-bitacora-list">
                @forelse ($dias as $dia)
                    <button type="button" class="fo-bitacora-choice" wire:key="dia-{{ $dia->id }}" wire:click="selectDia({{ $dia->id }})" @if ($selectedDiaId === $dia->id) aria-current="true" @endif>
                        <span>Día <span class="fo-num">{{ $dia->numero_dia }}</span>@if ($dia->fecha_dia) · <span class="fo-num">{{ $dia->fecha_dia->format('d-m-Y') }}</span>@endif</span>
                        @if (trim($searchDetalle) !== '')<span class="fo-badge fo-badge--num" aria-label="{{ $matchesPorDia[$dia->id] ?? 0 }} coincidencias">{{ $matchesPorDia[$dia->id] ?? 0 }}</span>@endif
                    </button>
                @empty
                    <p class="fo-bitacora-empty">{{ $selectedRit === '' ? 'Selecciona un juicio para ver sus días.' : 'No hay días que mostrar. Prueba con otra búsqueda o selecciona otro juicio.' }}</p>
                @endforelse
            </div>
        </section>

        <section class="fo-bitacora-panel fo-bitacora-detail" aria-labelledby="bitacora-detalle">
            <h2 id="bitacora-detalle">Detalle del día</h2>
            <label class="fo-listbar-field cm-focus-ring">
                <span class="sr-only">Buscar prueba, institución o usuario</span>
                <input type="search" wire:model.live.debounce.300ms="searchDetalle" placeholder="Buscar prueba, institución o usuario">
            </label>
            @if (! $selectedDia)
                <p class="fo-bitacora-empty">Selecciona un día para ver el detalle o cambia la búsqueda.</p>
            @else
                <p class="fo-bitacora-date">Día <span class="fo-num">{{ $selectedDia->numero_dia }}</span>@if ($selectedDia->fecha_dia) · <span class="fo-num">{{ $selectedDia->fecha_dia->format('d-m-Y') }}</span>@endif</p>
                @if ($selectedDia->items->isEmpty())
                    <p class="fo-bitacora-empty">No hay pruebas registradas para este día. Selecciona otro día.</p>
                @else
                    <div class="fo-table-wrap fo-table-wrap--glass fo-bitacora-table">
                        <table class="fo-table">
                            <caption class="sr-only">Pruebas del día seleccionado</caption>
                            <thead><tr><th scope="col">Prueba</th><th scope="col">Institución</th><th scope="col">Ingresado por</th></tr></thead>
                            <tbody>
                                @foreach ($selectedDia->items as $item)
                                    <tr wire:key="prueba-{{ $item->id }}">
                                        <td data-label="Prueba">{{ $item->nombre_prueba }}</td>
                                        <td data-label="Institución">{{ $item->institucion_exhibe }}</td>
                                        <td data-label="Ingresado por">{{ $item->usuario?->name ?? 'Usuario no disponible' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </section>
    </div>
</div>
