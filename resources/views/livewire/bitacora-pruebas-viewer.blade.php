<div @class([
    'bp-wrap relative max-w-7xl mx-auto px-4 py-10 space-y-6',
    'animate__animated animate__fadeInUp animate__faster' => $showHero,
])>
    @if ($showHero)
        <div class="absolute top-4 left-4 z-10">
            <a href="{{ url('/') }}" class="text-black font-semibold py-1 px-3 rounded-md flex items-center text-sm hover:text-white transition bo-panel-subtle">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                </svg>
                INICIO
            </a>
        </div>
    @endif

    @if ($showHero)
        <div class="bp-hero border rounded-xl p-5 bo-panel-subtle">
            <div class="flex items-center justify-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
                    <i class="fa-solid fa-clipboard-list text-[38px] text-[#2D8CFF]"></i>
                </div>

                <div class="text-left">
                    <h1 class="text-2xl font-bold bo-strong">Bitacora Pruebas</h1>
                    <p class="text-sm mt-1 bo-muted">Consulta publica de pruebas por juicio y desglose diario.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bp-grid grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bp-card bo-card p-4">
            <h2 class="text-lg font-semibold mb-3 bo-strong">Juicios registrados</h2>

            <input
                type="text"
                wire:model.live.debounce.300ms="searchRit"
                placeholder="Buscar RIT"
                class="w-full mb-3 bo-input text-sm"
            >

            @if ($rits->isEmpty())
                <p class="text-sm bo-muted">No hay juicios registrados.</p>
            @else
                <div class="space-y-2">
                    @foreach ($rits as $rit)
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="selectRit('{{ $rit }}')"
                                class="flex-1 text-left px-3 py-2 rounded-md border transition {{ trim($selectedRit) === trim($rit) ? 'bo-action-primary' : 'bo-panel bo-strong' }}"
                                title="Ver desglose por día"
                            >
                                {{ $rit }}
                            </button>

                            @if ($showHero)
                                @if (!empty($enlacesPorRit[$rit]))
                                    <a
                                        href="{{ $enlacesPorRit[$rit] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center px-2.5 py-2 rounded-md border text-xs font-semibold bo-panel-subtle bo-text--info"
                                        title="Abrir acta del juicio"
                                    >
                                        Ver
                                    </a>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-2 rounded-md border text-xs font-semibold bo-muted">
                                        Ver
                                    </span>
                                @endif
                            @elseif (!empty($enlacesPorRit[$rit]))
                                <a
                                    href="{{ $enlacesPorRit[$rit] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center px-2.5 py-2 rounded-md border text-xs font-semibold bo-panel-subtle bo-text--info"
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

        <div class="bp-card bo-card p-4">
            <h2 class="text-lg font-semibold mb-3 bo-strong">Desglose por día</h2>

            <input
                type="text"
                wire:model.live.debounce.300ms="searchDia"
                placeholder="Buscar día o fecha"
                class="w-full mb-3 bo-input text-sm"
            >

            @if ($selectedRit === '')
                <p class="text-sm bo-muted">Selecciona un RIT para ver sus dias.</p>
            @elseif ($dias->isEmpty())
                <p class="text-sm bo-muted">No hay dias cargados para este RIT.</p>
            @else
                <div class="space-y-2">
                    @foreach ($dias as $dia)
                        <button
                            type="button"
                            wire:click="selectDia({{ $dia->id }})"
                            class="w-full text-left px-3 py-2 rounded-md border transition {{ $selectedDiaId === $dia->id ? 'bo-action-primary' : 'bo-panel bo-strong' }}"
                        >
                            <span class="inline-flex w-full items-center justify-between gap-2">
                                <span>Dia {{ $dia->numero_dia }}</span>
                                @if (trim($searchDetalle) !== '')
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $selectedDiaId === $dia->id ? 'bg-white/25 text-white' : 'bg-sky-100 text-sky-700' }}">
                                        {{ $matchesPorDia[$dia->id] ?? 0 }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bp-card bo-card p-4">
            <h2 class="text-lg font-semibold mb-3 bo-strong">Bitácora diaria</h2>

            <input
                type="text"
                wire:model.live.debounce.300ms="searchDetalle"
                placeholder="Buscar prueba, institucion o usuario"
                class="w-full mb-3 bo-input text-sm"
            >

            @if (! $selectedDia)
                <p class="text-sm bo-muted">Selecciona un día para ver el detalle.</p>
            @else
                <div class="mb-3 p-3 rounded-md border bo-panel-subtle">
                    <p class="font-semibold bo-strong">Dia {{ $selectedDia->numero_dia }}</p>
                    <p class="text-sm bo-muted">Fecha: {{ $selectedDia->fecha_dia?->format('d-m-Y') }}</p>
                </div>

                @if ($selectedDia->items->isEmpty())
                    <p class="text-sm bo-muted">Sin pruebas registradas para este día.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($selectedDia->items as $item)
                            <article class="border rounded-md p-3">
                                <p class="font-medium bo-strong">{{ $item->nombre_prueba }}</p>
                                <p class="text-sm bo-muted">Institucion: {{ $item->institucion_exhibe }}</p>
                                <p class="text-sm bo-muted">Ingresado por: {{ $item->usuario?->name ?? 'Usuario no disponible' }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
