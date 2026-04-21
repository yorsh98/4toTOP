<div @class([
    'bp-wrap relative max-w-7xl mx-auto px-4 py-10 space-y-6',
    'animate__animated animate__fadeInUp animate__faster' => $showHero,
])>
    @if ($showHero)
        <div class="absolute top-4 left-4 z-10">
            <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                </svg>
                INICIO
            </a>
        </div>
    @endif

    @if ($showHero)
        <div class="bp-hero bg-white/90 backdrop-blur border border-sky-100 rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
                    <i class="fa-solid fa-clipboard-list text-[38px] text-[#2D8CFF]"></i>
                </div>

                <div class="text-left">
                    <h1 class="text-2xl font-bold text-gray-800">Bitacora Pruebas</h1>
                    <p class="text-sm text-gray-600 mt-1">Consulta publica de pruebas por juicio y desglose diario.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bp-grid grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bp-card bg-white/95 rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Juicios registrados</h2>

            <input
                type="text"
                wire:model.live.debounce.300ms="searchRit"
                placeholder="Buscar RIT"
                class="w-full mb-3 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500"
            >

            @if ($rits->isEmpty())
                <p class="text-sm text-gray-500">No hay juicios registrados.</p>
            @else
                <div class="space-y-2">
                    @foreach ($rits as $rit)
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="selectRit('{{ $rit }}')"
                                class="flex-1 text-left px-3 py-2 rounded-md border transition {{ $selectedRit === $rit ? 'bg-sky-600 text-white border-sky-600' : 'bg-white hover:bg-sky-50 border-gray-300 text-gray-800' }}"
                                title="Ver desglose por dia"
                            >
                                {{ $rit }}
                            </button>

                            @if ($showHero)
                                @if (!empty($enlacesPorRit[$rit]))
                                    <a
                                        href="{{ $enlacesPorRit[$rit] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center px-2.5 py-2 rounded-md border border-sky-300 text-sky-700 hover:bg-sky-50 text-xs font-semibold"
                                        title="Abrir acta del juicio"
                                    >
                                        Ver
                                    </a>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-2 rounded-md border border-gray-200 text-gray-400 text-xs font-semibold">
                                        Ver
                                    </span>
                                @endif
                            @elseif (!empty($enlacesPorRit[$rit]))
                                <a
                                    href="{{ $enlacesPorRit[$rit] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center px-2.5 py-2 rounded-md border border-sky-300 text-sky-700 hover:bg-sky-50 text-xs font-semibold"
                                    title="Abrir acta del juicio"
                                >
                                    Ver
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bp-card bg-white/95 rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Desglose por dia</h2>

            <input
                type="text"
                wire:model.live.debounce.300ms="searchDia"
                placeholder="Buscar dia o fecha"
                class="w-full mb-3 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500"
            >

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

            <input
                type="text"
                wire:model.live.debounce.300ms="searchDetalle"
                placeholder="Buscar prueba, institucion o usuario"
                class="w-full mb-3 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500"
            >

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
