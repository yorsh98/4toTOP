<div class="bp-wrap max-w-7xl mx-auto px-4 py-10 space-y-6">
    <div class="bp-hero bg-white/90 backdrop-blur border border-sky-100 rounded-xl shadow-sm p-5">
        <h1 class="text-2xl font-bold text-gray-800">Bitacora Pruebas</h1>
        <p class="text-sm text-gray-600 mt-1">Consulta publica de pruebas por juicio y desglose diario.</p>
    </div>

    <div class="bp-grid grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bp-card bg-white/95 rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Juicios registrados</h2>

            @if ($rits->isEmpty())
                <p class="text-sm text-gray-500">No hay juicios registrados.</p>
            @else
                <div class="space-y-2">
                    @foreach ($rits as $rit)
                        <button
                            type="button"
                            wire:click="selectRit('{{ $rit }}')"
                            class="w-full text-left px-3 py-2 rounded-md border transition {{ $selectedRit === $rit ? 'bg-sky-600 text-white border-sky-600' : 'bg-white hover:bg-sky-50 border-gray-300 text-gray-800' }}"
                        >
                            {{ $rit }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bp-card bg-white/95 rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Desglose por dia</h2>

            @if ($selectedRit === '')
                <p class="text-sm text-gray-500">Selecciona un RIT para ver sus dias.</p>
            @elseif ($dias->isEmpty())
                <p class="text-sm text-gray-500">No hay dias cargados para este RIT.</p>
            @else
                <div class="space-y-2">
                    @foreach ($dias as $dia)
                        <button
                            type="button"
                            wire:click="selectDia({{ $dia->id }})"
                            class="w-full text-left px-3 py-2 rounded-md border transition {{ $selectedDiaId === $dia->id ? 'bg-sky-600 text-white border-sky-600' : 'bg-white hover:bg-sky-50 border-gray-300 text-gray-800' }}"
                        >
                            Dia {{ $dia->numero_dia }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bp-card bg-white/95 rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Bitacora diaria</h2>

            @if (! $selectedDia)
                <p class="text-sm text-gray-500">Selecciona un dia para ver el detalle.</p>
            @else
                <div class="mb-3 p-3 rounded-md bg-sky-50 border border-sky-100">
                    <p class="font-semibold text-gray-800">Dia {{ $selectedDia->numero_dia }}</p>
                    <p class="text-sm text-gray-600">Fecha: {{ $selectedDia->fecha_dia?->format('d-m-Y') }}</p>
                </div>

                @if ($selectedDia->items->isEmpty())
                    <p class="text-sm text-gray-500">Sin pruebas registradas para este dia.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($selectedDia->items as $item)
                            <article class="border rounded-md p-3">
                                <p class="font-medium text-gray-800">{{ $item->nombre_prueba }}</p>
                                <p class="text-sm text-gray-600">Institucion: {{ $item->institucion_exhibe }}</p>
                                <p class="text-sm text-gray-500">Ingresado por: {{ $item->usuario?->name ?? 'Usuario no disponible' }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
