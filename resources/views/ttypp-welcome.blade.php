<x-in-layout title="TTyPP">
    @php
        $hoyStr = \Carbon\Carbon::now('America/Santiago')->toDateString();
        $prevStr = \Carbon\Carbon::parse($fecha, 'America/Santiago')->subDay()->toDateString();
        $nextStr = \Carbon\Carbon::parse($fecha, 'America/Santiago')->addDay()->toDateString();
        $columnas = [
            'rit' => 'RIT', 'ruc' => 'RUC', 'tipo_audiencia' => 'Tipo audiencia',
            'numero_dias' => 'Días', 'cantidad' => 'Cant.', 'modalidad' => 'Modalidad', 'sala' => 'Sala',
        ];
        $detalles = [
            'encargada_ti_mp' => 'Encargada TI MP', 'encargada_ti_d' => 'Encargada TI D',
            'encargada_zoom' => 'Encargada Zoom', 'acta' => 'Acta', 'jueces' => 'Jueces',
        ];
    @endphp

    <x-public.page-head title="TTyPP" lead="Unidad de Testigos y Peritos" :meta="ucfirst($fechaHuman)" />

    <div class="fo-listbar fo-ttypp-bar">
        <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $prevStr }}">‹ Anterior</a>
        <form method="GET" action="{{ url()->current() }}" class="fo-ttypp-date">
            <label class="sr-only" for="ttypp-fecha">Fecha</label>
            <input class="fo-num" id="ttypp-fecha" type="date" name="fecha" value="{{ $fecha }}">
            <button class="fo-ttypp-button" type="submit">Ver</button>
        </form>
        <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $hoyStr }}">Hoy</a>
        <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $nextStr }}">Siguiente ›</a>
    </div>

    <div class="fo-table-wrap fo-table-wrap--glass fo-ttypp-table">
        <table class="fo-table">
            <caption class="sr-only">Listado TTyPP para {{ $fechaHuman }}</caption>
            <thead><tr>
                @foreach ($columnas as $etiqueta)
                    <th scope="col">{{ $etiqueta }}</th>
                @endforeach
                <th scope="col"><span class="sr-only">Detalle</span></th>
            </tr></thead>
            @forelse ($registros as $registro)
                <tbody class="fo-table-group" x-data="{ open: false }">
                    <tr>
                        @foreach ($columnas as $campo => $etiqueta)
                            <td data-label="{{ $etiqueta }}">
                                @if (filled($registro->$campo) && $registro->$campo !== '-')
                                    <span @class(['fo-num' => in_array($campo, ['rit', 'ruc'])])>{{ $registro->$campo }}</span>
                                @else
                                    <span class="fo-dash">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="fo-table-toggle">
                            <button type="button" class="fo-icon-btn cm-press" x-on:click="open = ! open" x-bind:aria-expanded="open"
                                    aria-expanded="false" aria-controls="ttypp-det-{{ $registro->id }}" aria-label="Ver detalle del registro {{ $registro->rit ?: $registro->id }}">
                                <i class="fa-solid fa-chevron-down" x-bind:class="open && 'fa-rotate-180'" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="fo-table-detail" id="ttypp-det-{{ $registro->id }}" x-show="open" x-cloak>
                        <td colspan="8"><dl class="fo-detail">
                            @foreach ($detalles as $campo => $etiqueta)
                                <div><dt>{{ $etiqueta }}</dt><dd>
                                    @if (filled($registro->$campo) && $registro->$campo !== '-')
                                        {{ $registro->$campo }}
                                    @else
                                        <span class="fo-dash">—</span>
                                    @endif
                                </dd></div>
                            @endforeach
                        </dl></td>
                    </tr>
                </tbody>
            @empty
                <tbody><tr><td colspan="8" class="fo-table-empty">
                    No hay registros TTyPP para {{ $fechaHuman }}. Prueba con otro día.
                    <a class="fo-link" href="{{ url()->current() }}?fecha={{ $hoyStr }}">Hoy</a>
                </td></tr></tbody>
            @endforelse
        </table>
    </div>
</x-in-layout>
