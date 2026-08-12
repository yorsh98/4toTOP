@unless ($isPublic)
    <x-slot:header>
        <x-page-heading eyebrow="Servicios · Mapa" title="Mapa interactivo" />
    </x-slot:header>
@endunless

<div>
    @if ($isPublic)
    <div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__backInUp animate__delay-0_3s">
        <div class="absolute top-4 left-4">
            <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                INICIO
            </a>
        </div>

        <div class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 lg:pb-10">
            <div class="pt-3 sm:pt-5 text-center">
                <h2 class="text-xl font-semibold text-black">Mapa Servicios</h2>
                <p class="mt-2 text-sm text-slate-600">Unidad de Servicios del Tribunal</p>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05]">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-black">Mapa Servicios</h3>
                    <p class="mt-1 text-sm text-slate-600">Plano interactivo y activos registrados en inventario.</p>
                </div>

                <div class="grid gap-2 sm:grid-cols-[auto_minmax(14rem,20rem)] sm:items-center">
                    <div class="flex rounded-lg bg-slate-100 p-1">
                        @foreach ($availableFloors as $floor)
                            <a href="{{ route('servicios.mapa-publico', ['piso' => $floor, 'search' => $search ?: null]) }}" class="rounded-md px-3 py-1.5 text-sm font-semibold transition {{ $piso === $floor ? 'bg-blue-700 text-white shadow' : 'text-slate-600 hover:bg-white' }}">
                                Piso {{ $floor }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('servicios.mapa-publico') }}" class="block">
                        <input type="hidden" name="piso" value="{{ $piso }}">
                        <label class="block">
                            <span class="sr-only">Buscar activos</span>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Buscar activo o sector..." class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </form>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="rounded-md bg-slate-900">
                    <div class="relative aspect-[4/3] w-full overflow-hidden bg-white" aria-label="Plano interactivo del piso {{ $piso }}">
                        <img src="{{ $imagePath }}" alt="Plano del piso {{ $piso }} del tribunal" class="absolute inset-0 h-full w-full object-cover">

                        @forelse ($hotspots as $hotspot)
                            <button
                                type="button"
                                wire:key="public-hotspot-{{ $piso }}-{{ $hotspot['identificador'] }}"
                                style="left: {{ ($hotspot['x'] / 1440) * 100 }}%; top: {{ ($hotspot['y'] / 1080) * 100 }}%; width: {{ ($hotspot['w'] / 1440) * 100 }}%; height: {{ ($hotspot['h'] / 1080) * 100 }}%;"
                                class="group absolute flex items-center justify-center rounded border px-1 text-center text-[8px] font-black uppercase leading-tight shadow-sm transition hover:z-20 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-blue-600/75 hover:text-white focus:z-20 focus:outline-none focus:ring-2 focus:ring-cyan-300 sm:text-[10px] lg:text-[11px] {{ $hotspot['has_data'] ? 'border-slate-950 bg-slate-950/70 text-white' : 'border-slate-500 bg-slate-600/40 text-white' }}"
                                title="{{ $hotspot['label'] }} - {{ $hotspot['activos_count'] }} activos"
                            >
                                <span class="max-w-full truncate drop-shadow">{{ $hotspot['label'] }}</span>
                                <span class="absolute -right-1 -top-1 rounded-full border border-white bg-white px-1 py-0.5 text-[8px] font-black text-slate-950 shadow sm:-right-2 sm:-top-2 sm:px-1.5 sm:text-[10px]">
                                    {{ $hotspot['activos_count'] }}
                                </span>
                            </button>
                        @empty
                            <div class="absolute inset-x-8 top-8 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900 shadow">
                                El plano del Piso {{ $piso }} esta disponible, pero sus hotspots aun no fueron cargados.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Activo</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Sector</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Piso</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($publicActivos as $activo)
                            <tr wire:key="public-activo-row-{{ $activo->id }}">
                                <td class="px-3 py-2 text-slate-700">{{ $activo->nombre }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->sector?->nombre ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->sector?->piso ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->estado }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-slate-500">No hay activos para los filtros actuales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
<div class="bg-slate-100 py-6">
    <div class="mx-auto {{ $isPublic ? 'max-w-7xl space-y-4 px-0 sm:px-2' : 'max-w-[1540px] space-y-6 px-4 sm:px-6 lg:px-8' }}">
        <section class="overflow-hidden border border-blue-900/20 bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700 text-white shadow-xl {{ $isPublic ? 'rounded-2xl' : 'rounded-[2rem]' }}">
            <div class="grid gap-4 {{ $isPublic ? 'p-4 sm:p-5' : 'p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:p-8' }}">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.32em] text-cyan-100">Servicios institucionales</p>
                    <h3 class="font-black tracking-tight {{ $isPublic ? 'mt-2 text-2xl sm:text-3xl' : 'mt-3 text-3xl sm:text-4xl' }}">{{ $isPublic ? 'Mapa Servicios' : 'Mapa interactivo de activos' }}</h3>
                    <p class="max-w-3xl text-sm leading-6 text-blue-50 {{ $isPublic ? 'mt-2' : 'mt-3 sm:text-base' }}">
                        {{ $isPublic ? 'Plano de orientacion de las dependencias del tribunal, disponible sin autenticacion.' : 'Plano real del tribunal con hotspots por oficina, bodegas, salas y areas de servicio.' }}
                    </p>
                </div>
                @unless ($isPublic)
                    <a href="{{ route('backoffice.servicios.inventario') }}" wire:navigate class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-blue-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-cyan-50">
                        Ver inventario
                    </a>
                @endunless
            </div>
        </section>

        <section class="grid gap-3 {{ $isPublic ? 'grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-2 xl:grid-cols-4' }}">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm {{ $isPublic ? 'p-3 sm:p-4' : 'p-5' }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Planta</p>
                <p class="mt-2 font-black text-slate-950 {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">Piso {{ $piso }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm {{ $isPublic ? 'p-3 sm:p-4' : 'p-5' }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sectores registrados</p>
                <p class="mt-2 font-black text-slate-950 {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">{{ number_format($totalSectores) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm {{ $isPublic ? 'p-3 sm:p-4' : 'p-5' }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Activos registrados</p>
                <p class="mt-2 font-black text-slate-950 {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">{{ number_format($totalActivos) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm {{ $isPublic ? 'p-3 sm:p-4' : 'p-5' }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sector seleccionado</p>
                <p class="truncate text-sm font-semibold text-slate-700 {{ $isPublic ? 'mt-2' : 'mt-3' }}">{{ $selectedSector?->nombre ?? $selectedHotspot['label'] ?? 'Sin seleccion' }}</p>
            </div>
        </section>

        <section class="border border-slate-200 bg-white shadow-sm {{ $isPublic ? 'rounded-2xl p-3 sm:p-4' : 'rounded-[2rem] p-4 sm:p-5' }}">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Plano real Piso {{ $piso }}</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-950">PLANTA: Piso {{ $piso }}</h3>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Haz click sobre un area para abrir el detalle. Los badges azules tienen activos registrados; los grises aun no tienen inventario cargado.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-[auto_minmax(16rem,24rem)] sm:items-center">
                    <div class="flex rounded-2xl bg-slate-100 p-1">
                        @foreach ($availableFloors as $floor)
                            <button type="button" wire:click="selectPiso({{ $floor }})" class="rounded-xl px-4 py-2 text-sm font-bold transition {{ $piso === $floor ? 'bg-blue-700 text-white shadow' : 'text-slate-600 hover:bg-white' }}">
                                Piso {{ $floor }}
                            </button>
                        @endforeach
                    </div>

                    <label class="block">
                        <span class="sr-only">Buscar activos</span>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ $isPublic ? 'Buscar activo o sector...' : 'Buscar activo, codigo, serie o sector...' }}" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                </div>
            </div>

            <div class="mt-5 border border-slate-300 bg-slate-950 shadow-inner {{ $isPublic ? 'rounded-2xl p-2' : 'rounded-3xl p-3' }}">
                <div class="overflow-x-auto rounded-2xl bg-slate-900">
                    <div class="relative h-[1080px] w-[1440px] overflow-hidden bg-white" aria-label="Plano interactivo del piso {{ $piso }}">
                        <img src="{{ $imagePath }}" alt="Plano del piso {{ $piso }} del tribunal" class="absolute inset-0 h-full w-full object-cover">

                        @forelse ($hotspots as $hotspot)
                            <button
                                type="button"
                                wire:key="hotspot-{{ $piso }}-{{ $hotspot['identificador'] }}"
                                wire:click="selectHotspot(@js($hotspot['identificador']))"
                                style="left: {{ $hotspot['x'] }}px; top: {{ $hotspot['y'] }}px; width: {{ $hotspot['w'] }}px; height: {{ $hotspot['h'] }}px;"
                                class="group absolute flex items-center justify-center rounded-md border-2 px-2 text-center text-[11px] font-black uppercase leading-tight shadow-sm transition hover:z-20 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-blue-600/75 hover:text-white focus:z-20 focus:outline-none focus:ring-4 focus:ring-cyan-300 {{ ($selectedHotspot['identificador'] ?? null) === $hotspot['identificador'] ? 'z-10 border-cyan-300 bg-blue-700/80 text-white ring-4 ring-cyan-200' : ($hotspot['has_data'] ? 'border-slate-950 bg-slate-950/70 text-white' : 'border-slate-500 bg-slate-600/40 text-white') }}"
                                title="{{ $hotspot['label'] }} - {{ $hotspot['activos_count'] }} activos"
                            >
                                <span class="drop-shadow">{{ $hotspot['label'] }}</span>
                                <span class="absolute -right-2 -top-2 rounded-full border border-white bg-white px-1.5 py-0.5 text-[10px] font-black text-slate-950 shadow">
                                    {{ $hotspot['activos_count'] }}
                                </span>
                            </button>
                        @empty
                            <div class="absolute inset-x-8 top-8 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900 shadow">
                                El plano del Piso {{ $piso }} esta disponible, pero sus hotspots aun no fueron cargados.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-600">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-950"></span>Con registros</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-500"></span>Sin activos</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-700"></span>Seleccionado</span>
            </div>

            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                El sector donde el plano indica 929 Claudia M. fue cruzado con el inventario como 834, porque asi aparece registrado en el Excel.
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-950">Inventario encontrado</h3>
                    <p class="text-sm text-slate-600">{{ $activos->count() }} {{ $activos->count() === 1 ? 'registro' : 'registros' }} en Piso {{ $piso }}</p>
                </div>
                @if ($search !== '')
                    <button type="button" wire:click="$set('search', '')" class="self-start rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 sm:self-auto">
                        Limpiar busqueda
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Activo</th>
                            @unless ($isPublic)
                                <th class="px-5 py-3">Codigo</th>
                                <th class="px-5 py-3">Serie</th>
                            @endunless
                            <th class="px-5 py-3">Sector</th>
                            <th class="px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($activos as $activo)
                            <tr wire:key="activo-row-{{ $activo->id }}" class="hover:bg-blue-50/60">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $activo->nombre }}</td>
                                @unless ($isPublic)
                                    <td class="px-5 py-4 text-slate-600">{{ $activo->codigo_inventario ?: 'Sin codigo' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $activo->numero_serie ?: 'Sin serie' }}</td>
                                @endunless
                                <td class="px-5 py-4 text-slate-600">{{ $activo->sector?->nombre }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $activo->estado }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isPublic ? 3 : 5 }}" class="px-5 py-8 text-center text-sm text-slate-500">No hay activos para los filtros actuales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($sectoresSinPosicion->isNotEmpty())
            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-700">Sectores sin posicion en el plano</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($sectoresSinPosicion as $sector)
                        <button type="button" wire:key="sector-extra-{{ $sector->id }}" wire:click="selectSector({{ $sector->id }})" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                            {{ $sector->nombre }} ({{ $sector->activos_count }})
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
    </div>
    @endif

    @if (! $isPublic && ($selectedSector || $selectedHotspot))
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-4 sm:items-center" role="dialog" aria-modal="true">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-[2rem] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Detalle de sector</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-950">{{ $selectedSector?->nombre ?? $selectedHotspot['label'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Identificador: {{ $selectedSector?->identificador_mapa ?? $selectedHotspot['identificador'] }}</p>
                    </div>
                    <button type="button" wire:click="closeSectorDetail" class="rounded-full border border-slate-300 px-3 py-1 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                        Cerrar
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-5">
                    @if ($selectedSector)
                        <div class="mb-4 rounded-2xl bg-blue-50 p-4 text-sm font-semibold text-blue-900">
                            {{ $selectedSector->activos->count() }} {{ $selectedSector->activos->count() === 1 ? 'activo registrado' : 'activos registrados' }} en este sector.
                        </div>

                        <div class="space-y-3">
                            @forelse ($selectedSector->activos as $activo)
                                <article wire:key="sector-activo-{{ $activo->id }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-bold text-slate-950">{{ $activo->nombre }}</p>
                                            @unless ($isPublic)
                                                <p class="mt-1 text-xs text-slate-600">{{ $activo->codigo_inventario ?: 'Sin codigo inventario' }}</p>
                                            @endunless
                                        </div>
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-slate-700 shadow-sm">{{ $activo->estado }}</span>
                                    </div>

                                    @if (! $isPublic && ($activo->numero_serie || $activo->observaciones))
                                        <div class="mt-3 space-y-1 text-xs text-slate-600">
                                            @if ($activo->numero_serie)
                                                <p>Serie: {{ $activo->numero_serie }}</p>
                                            @endif
                                            @if ($activo->observaciones)
                                                <p>{{ $activo->observaciones }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">
                                    Este sector no tiene activos registrados.
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">
                            Este hotspot existe en el plano, pero no tiene sector asociado en el inventario.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
