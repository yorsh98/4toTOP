@php
    // "10° Juzgado de Garantia de Santiago" → tipo "Juzgado de Garantia", lugar "Santiago", número "10°".
    // Lo que distingue a cada tribunal es el lugar; el tipo sirve para agrupar.
    $grupos = collect($tribunales)
        ->map(function (array $tribunal) {
            $nombre = $tribunal['nombre'];
            $numero = preg_match('/^\s*(\d+\s*°)\s*/u', $nombre, $m) ? str_replace(' ', '', $m[1]) : null;
            $resto = $numero ? trim(substr($nombre, strlen($m[0]))) : $nombre;
            $partes = preg_match('/^(.*\b(?:Garant[ií]a|Penal|Letras))\s+de\s+(.+)$/iu', $resto, $p)
                ? ['tipo' => $p[1], 'lugar' => $p[2]]
                : ['tipo' => 'Otros tribunales', 'lugar' => $resto];

            return [...$partes, 'numero' => $numero, 'nombre' => $nombre];
        })
        ->sortBy(fn ($t) => [Str::ascii(mb_strtolower($t['lugar'])), (int) $t['numero']])
        ->groupBy('tipo')
        ->sortByDesc(fn ($items) => $items->count());
@endphp

<x-in-layout title="Migración UNIJUD">
    <x-public.list-page
        title="Migración UNIJUD"
        lead="Tribunales que ya trabajan en UNIJUD."
        :meta="$updatedAt ? 'Lista actualizada el '.$updatedAt : 'Sin fecha de actualización registrada'"
        :total="count($tribunales)"
        :noun="['tribunal', 'tribunales']"
        placeholder="Buscar por ciudad o tipo de tribunal"
        empty="Aún no hay tribunales en la lista. Se agregan desde el backoffice, en Documentos.">
        @if ($grupos->count() > 1)
            <x-slot:toolbar>
                <nav class="fo-chips" aria-label="Tipos de tribunal">
                    @foreach ($grupos as $tipo => $items)
                        <a href="#tipo-{{ Str::slug($tipo) }}" class="fo-chip cm-press">{{ $tipo }} <span class="fo-num">{{ $items->count() }}</span></a>
                    @endforeach
                </nav>
            </x-slot:toolbar>
        @endif

        @foreach ($grupos as $tipo => $items)
            <section class="fo-list-group" id="tipo-{{ Str::slug($tipo) }}" data-fo-group aria-labelledby="tipo-{{ Str::slug($tipo) }}-title">
                <h2 class="fo-list-group-title" id="tipo-{{ Str::slug($tipo) }}-title">
                    {{ $tipo }} <span class="fo-num">{{ $items->count() }}</span>
                </h2>
                <ul class="fo-rows fo-rows--grid">
                    @foreach ($items as $tribunal)
                        <x-public.list-row
                            :title="$tribunal['lugar']"
                            :keywords="$tribunal['nombre']"
                            :badges="$tribunal['numero'] ? [['label' => $tribunal['numero'], 'class' => 'fo-badge--num']] : []" />
                    @endforeach
                </ul>
            </section>
        @endforeach
    </x-public.list-page>
</x-in-layout>
