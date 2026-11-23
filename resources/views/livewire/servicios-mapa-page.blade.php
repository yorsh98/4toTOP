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
                <h2 class="text-xl font-semibold text-black">Inventario</h2>
                <p class="mt-2 text-sm text-slate-600">Unidad de Servicios</p>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05]">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-black">Mapa Tribunal</h3>
                    <p class="mt-1 text-sm text-slate-600">Plano interactivo y activos registrados en inventario.</p>
                </div>

                <div class="grid gap-2 sm:grid-cols-[auto_minmax(14rem,20rem)] sm:items-center">
                    <div class="flex rounded-lg bg-slate-100 p-1">
                        @foreach ($availableFloors as $floor)
                            <button type="button" wire:click="selectPiso({{ $floor }})" wire:loading.attr="disabled" class="rounded-md px-3 py-1.5 text-sm font-semibold transition {{ $piso === $floor ? 'bg-blue-700 text-white shadow' : 'text-slate-600 hover:bg-white' }}">
                                Piso {{ $floor }}
                                @if ($floorResults[$floor] > 0)
                                    <span @if ($piso !== $floor) style="color: #0891b2; font-weight: bold;" @endif>({{ $floorResults[$floor] }})</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <label class="block">
                        <span class="sr-only">Buscar activos</span>
                        <input type="search" wire:model.live.debounce.500ms="search" placeholder="Buscar activo o sector..." class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @if ($search !== '')
                            <span class="mt-1 block text-xs font-semibold text-cyan-700">{{ $mapSearchResultCount }} {{ $mapSearchResultCount === 1 ? 'sector encontrado en el plano' : 'sectores encontrados en el plano' }}</span>
                        @endif
                        @if ($searchTerm !== '' && $floorResults[$piso] === 0 && count($otherFloors))
                            <span class="mt-1 block text-xs">Sin coincidencias en Piso {{ $piso }}; encontrado en {{ implode(' y ', array_map(fn ($floor) => 'Piso '.$floor, $otherFloors)) }}</span>
                        @endif
                        @if ($bodegas->isNotEmpty())
                            <span class="mt-1 block text-xs">Objeto encontrado en bodega: {{ $bodegas->join(', ') }}</span>
                        @endif
                    </label>
                </div>
            </div>

            <x-servicios-plano :piso="$piso" :hotspots="$hotspots" :selected-hotspot="$selectedHotspot['identificador'] ?? null">
                <div class="relative aspect-[4/3] w-full overflow-hidden bg-white" aria-label="Plano interactivo del piso {{ $piso }}">
                    <img src="{{ $imagePath }}" alt="Plano del piso {{ $piso }} del tribunal" class="absolute inset-0 h-full w-full object-cover">

                    @forelse ($hotspots as $hotspot)
                        <button
                            type="button"
                            wire:key="public-hotspot-{{ $piso }}-{{ $hotspot['identificador'] }}"
                            wire:click="selectHotspot(@js($hotspot['identificador']))"
                            style="left: {{ ($hotspot['x'] / 1440) * 100 }}%; top: {{ ($hotspot['y'] / 1080) * 100 }}%; width: {{ ($hotspot['w'] / 1440) * 100 }}%; height: {{ ($hotspot['h'] / 1080) * 100 }}%;"
                            class="group absolute flex items-center justify-center rounded border px-1 text-center text-[8px] font-black uppercase leading-tight shadow-sm transition hover:z-20 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-blue-700 hover:text-white focus:z-20 focus:outline-none focus:ring-2 focus:ring-cyan-300 sm:text-[10px] lg:text-[11px] {{ $hotspot['is_map_match'] ? 'z-10 border-cyan-300 bg-cyan-600/80 text-white ring-2 ring-cyan-200' : (($hotspot['has_data'] ? 'border-blue-700 bg-blue-600/75 text-white' : 'border-blue-300 bg-blue-200/70 text-blue-950').($hotspot['is_map_dimmed'] ? ' opacity-30' : '')) }}"
                            title="{{ $hotspot['label'] }} - {{ $hotspot['activos_count'] }} activos"
                        >
                            <span class="max-w-full truncate drop-shadow">{{ $hotspot['label'] }}</span>
                            <span class="absolute -right-1 -top-1 rounded-full border border-white bg-white px-1 py-0.5 text-[8px] font-black text-slate-950 shadow sm:-right-2 sm:-top-2 sm:px-1.5 sm:text-[10px]">
                                {{ $hotspot['activos_count'] }}
                            </span>
                        </button>
                    @empty
                        <div class="absolute inset-x-8 top-8 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-900 shadow">
                            El plano del Piso {{ $piso }} esta disponible, pero sus hotspots aun no fueron cargados.
                        </div>
                    @endforelse
                </div>
            </x-servicios-plano>

            <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Activo</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Código</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Serie</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Sector</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($publicActivos as $activo)
                            <tr wire:key="public-activo-row-{{ $activo->id }}">
                                <td class="px-3 py-2 text-slate-700">{{ $activo->nombre }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->codigo_inventario ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->numero_serie ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->sector?->nombre ?? '-' }}</td>
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
<div class="py-6 bo-panel-subtle">
    <div class="mx-auto {{ $isPublic ? 'max-w-7xl space-y-4 px-0 sm:px-2' : 'max-w-[1540px] space-y-6 px-4 sm:px-6 lg:px-8' }}">
        <section class="overflow-hidden border border-blue-900/20 bo-panel bo-strong shadow-xl {{ $isPublic ? 'rounded-2xl' : 'rounded-[2rem]' }}">
            <div class="grid gap-4 {{ $isPublic ? 'p-4 sm:p-5' : 'p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:p-8' }}">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.32em]">Servicios institucionales</p>
                    <h3 class="font-black tracking-tight {{ $isPublic ? 'mt-2 text-2xl sm:text-3xl' : 'mt-3 text-3xl sm:text-4xl' }}">{{ $isPublic ? 'Mapa Servicios' : 'Mapa interactivo de activos' }}</h3>
                    <p class="max-w-3xl text-sm leading-6 bo-muted {{ $isPublic ? 'mt-2' : 'mt-3 sm:text-base' }}">
                        {{ $isPublic ? 'Plano de orientacion de las dependencias del tribunal, disponible sin autenticacion.' : 'Plano real del tribunal con hotspots por oficina, bodegas, salas y áreas de servicio.' }}
                    </p>
                </div>
                @unless ($isPublic)
                    <a href="{{ route('backoffice.servicios.inventario') }}" wire:navigate class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-bold transition hover:-translate-y-0.5 hover:bg-cyan-50 bo-panel-subtle bo-text--info">
                        Ver inventario
                    </a>
                @endunless
            </div>
        </section>

        <section class="grid gap-3 {{ $isPublic ? 'grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-2 xl:grid-cols-4' }}">
            <div class="bo-kpi p-5">
                <p class="text-xs font-bold uppercase tracking-wider bo-muted">Planta</p>
                <p class="mt-2 font-black bo-strong {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">Piso {{ $piso }}</p>
            </div>
            <div class="bo-kpi p-5">
                <p class="text-xs font-bold uppercase tracking-wider bo-muted">Sectores registrados</p>
                <p class="mt-2 font-black bo-strong {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">{{ number_format($totalSectores) }}</p>
            </div>
            <div class="bo-kpi p-5">
                <p class="text-xs font-bold uppercase tracking-wider bo-muted">Activos registrados</p>
                <p class="mt-2 font-black bo-strong {{ $isPublic ? 'text-2xl' : 'text-3xl' }}">{{ number_format($totalActivos) }}</p>
            </div>
            @if ($isPublic)
                <div class="rounded-2xl border p-3 sm:p-4 bo-panel-subtle">
                    <p class="text-xs font-bold uppercase tracking-wider bo-muted">Sector seleccionado</p>
                    <p class="truncate mt-2 text-sm font-semibold bo-strong">{{ $selectedSector?->nombre ?? $selectedHotspot['label'] ?? 'Sin seleccion' }}</p>
                </div>
            @else
                <div class="rounded-2xl border p-5 bo-panel-subtle">
                    <p class="text-xs font-bold uppercase tracking-wider bo-muted">Stock en bodegas</p>
                    <p class="mt-2 text-3xl font-black bo-strong">{{ number_format($totalInsumos) }}</p>
                </div>
            @endif
        </section>

        <section class="bo-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.24em] bo-text--info">Plano real Piso {{ $piso }}</p>
                    <h3 class="mt-2 text-2xl font-black bo-strong">PLANTA: Piso {{ $piso }}</h3>
                    <p class="mt-2 max-w-3xl text-sm bo-muted">
                        Haz clic sobre un área para abrir el detalle. Los badges azules tienen activos registrados; los grises aún no tienen inventario cargado.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-[auto_minmax(16rem,24rem)] sm:items-center">
                    <div class="flex rounded-2xl p-1 bo-segmented">
                        @foreach ($availableFloors as $floor)
                            <button type="button" wire:click="selectPiso({{ $floor }})" class="bo-segmented-item rounded-xl px-4 py-2 text-sm font-bold transition {{ $piso === $floor ? 'is-active' : '' }}" @if ($piso === $floor) aria-current="true" @endif>
                                Piso {{ $floor }}
                                @if ($floorResults[$floor] > 0)
                                    <span @if ($piso !== $floor) style="color: #0891b2; font-weight: bold;" @endif>({{ $floorResults[$floor] }})</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <label class="block">
                        <span class="sr-only">Buscar en el mapa</span>
                        <input type="search" wire:model.live.debounce.300ms="{{ $isPublic ? 'search' : 'mapSearch' }}" placeholder="{{ $isPublic ? 'Buscar activo o sector...' : 'Buscar activo, código, serie o sector en el mapa...' }}" class="w-full bo-input text-sm">
                        @if (! $isPublic && $mapSearch !== '')
                            <span class="mt-1 block text-xs font-semibold bo-text--info">{{ $mapSearchResultCount }} {{ $mapSearchResultCount === 1 ? 'sector encontrado en el plano' : 'sectores encontrados en el plano' }}</span>
                        @endif
                        @if ($searchTerm !== '' && $floorResults[$piso] === 0 && count($otherFloors))
                            <span class="mt-1 block text-xs">Sin coincidencias en Piso {{ $piso }}; encontrado en {{ implode(' y ', array_map(fn ($floor) => 'Piso '.$floor, $otherFloors)) }}</span>
                        @endif
                        @if ($bodegas->isNotEmpty())
                            <span class="mt-1 block text-xs">Objeto encontrado en bodega: {{ $bodegas->join(', ') }}</span>
                        @endif
                    </label>
                </div>
            </div>

            <x-servicios-plano :piso="$piso" :hotspots="$hotspots" :selected-hotspot="$selectedHotspot['identificador'] ?? null">
                <div class="relative h-[1080px] w-[1440px] overflow-hidden bo-panel-subtle" aria-label="Plano interactivo del piso {{ $piso }}">
                    <img src="{{ $imagePath }}" alt="Plano del piso {{ $piso }} del tribunal" class="absolute inset-0 h-full w-full object-cover">

                    @forelse ($hotspots as $hotspot)
                        <button
                            type="button"
                            wire:key="hotspot-{{ $piso }}-{{ $hotspot['identificador'] }}"
                            wire:click="selectHotspot(@js($hotspot['identificador']))"
                            style="left: {{ ($hotspot['x'] / 1440) * 100 }}%; top: {{ ($hotspot['y'] / 1080) * 100 }}%; width: {{ ($hotspot['w'] / 1440) * 100 }}%; height: {{ ($hotspot['h'] / 1080) * 100 }}%;"
                            aria-label="{{ $hotspot['label'] }}, {{ $hotspot['activos_count'] }} activos{{ $isPublic || $hotspot['insumos_count'] === 0 ? '' : ', '.$hotspot['insumos_count'].' insumos' }}"
                            class="group absolute flex items-center justify-center rounded-md border-2 px-2 text-center text-[11px] font-black uppercase leading-tight shadow-sm transition hover:z-20 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-blue-700 hover:text-white focus:z-20 focus:outline-none focus:ring-4 focus:ring-cyan-300 {{ ($selectedHotspot['identificador'] ?? null) === $hotspot['identificador'] ? 'z-10 border-cyan-300 bg-blue-700/80 text-white ring-4 ring-cyan-200' : ($hotspot['is_map_match'] ? 'z-10 border-cyan-300 bg-cyan-600/80 text-white ring-4 ring-cyan-200' : (($hotspot['has_data'] ? 'border-blue-700 bg-blue-600/75 text-white' : 'border-blue-300 bg-blue-200/70 text-blue-950').($hotspot['is_map_dimmed'] ? ' opacity-30' : '')) ) }}"
                            title="{{ $hotspot['label'] }} - {{ $hotspot['activos_count'] }} activos{{ $isPublic || $hotspot['insumos_count'] === 0 ? '' : ' - '.$hotspot['insumos_count'].' insumos' }}"
                        >
                            <span class="drop-shadow">{{ $hotspot['label'] }}</span>
                            <span class="absolute -right-2 -top-2 rounded-full border border-white bg-white px-1.5 py-0.5 text-[10px] font-black text-slate-950 shadow">
                                {{ ! $isPublic && $hotspot['insumos_count'] > 0 ? $hotspot['activos_count'].' / '.$hotspot['insumos_count'] : $hotspot['activos_count'] }}
                            </span>
                        </button>
                    @empty
                        <div class="absolute inset-x-8 top-8 rounded-2xl border p-4 text-sm font-semibold bo-panel-subtle bo-text--info">
                            El plano del Piso {{ $piso }} está disponible, pero sus hotspots aún no fueron cargados.
                        </div>
                    @endforelse
                </div>
            </x-servicios-plano>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-semibold bo-muted">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-600"></span>Con registros</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-200"></span>Sin activos</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-700"></span>Seleccionado</span>
            </div>

        </section>

        <section class="rounded-[2rem] border bo-panel-subtle">
            <div class="flex flex-col gap-2 border-b p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black bo-strong">Inventario encontrado</h3>
                    <p class="text-sm bo-muted">{{ $activos->count() }} {{ $activos->count() === 1 ? 'registro' : 'registros' }} en Piso {{ $piso }}</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <label class="block sm:w-64">
                        <span class="sr-only">Buscar en inventario encontrado</span>
                        <input type="search" wire:model.live.debounce.300ms="inventorySearch" placeholder="Buscar en inventario..." class="w-full bo-input text-sm">
                    </label>
                    @if ($inventorySearch !== '')
                        <button type="button" wire:click="$set('inventorySearch', '')" class="self-start rounded-xl border px-3 py-2 text-xs font-bold transition sm:self-auto bo-panel-subtle bo-muted">
                            Limpiar busqueda
                        </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="bo-table min-w-full text-sm">
                    <thead class="text-left text-xs font-bold uppercase tracking-wider bo-panel-subtle bo-muted">
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
                    <tbody class="divide-y divide-slate-100 bo-panel-subtle">
                        @forelse ($activos as $activo)
                            <tr wire:key="activo-row-{{ $activo->id }}" class="bo-panel-subtle">
                                <td class="px-5 py-4 font-semibold bo-strong">{{ $activo->nombre }}</td>
                                @unless ($isPublic)
                                    <td class="px-5 py-4 bo-muted">{{ $activo->codigo_inventario ?: 'Sin codigo' }}</td>
                                    <td class="px-5 py-4 bo-muted">{{ $activo->numero_serie ?: 'Sin serie' }}</td>
                                @endunless
                                <td class="px-5 py-4 bo-muted">{{ $activo->sector?->nombre }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold bo-panel-subtle bo-strong">{{ $activo->estado }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isPublic ? 3 : 5 }}" class="px-5 py-8 text-center text-sm bo-muted">No hay activos para los filtros actuales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($sectoresSinPosicion->isNotEmpty())
            <section class="rounded-[2rem] border p-5 bo-panel-subtle">
                <h3 class="text-sm font-black uppercase tracking-wider bo-strong">Sectores sin posicion en el plano</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($sectoresSinPosicion as $sector)
                        <button type="button" wire:key="sector-extra-{{ $sector->id }}" wire:click="selectSector({{ $sector->id }})" class="rounded-full border px-3 py-1 text-xs font-bold transition bo-panel-subtle bo-strong">
                            {{ $sector->nombre }} ({{ $sector->activos_count }})
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
    </div>
    @endif

    @if ($selectedSector || $selectedHotspot)
        <div class="model-detail" role="dialog" aria-label="Detalle de sector">
            <div class="{{ $isPublic ? 'model-detail-card w-full overflow-hidden rounded-t-2xl bg-white shadow-2xl' : 'model-detail-card w-full overflow-hidden bo-card' }}">
                <div class="{{ $isPublic ? 'flex items-start justify-between gap-4 border-b border-slate-200 p-5' : 'flex items-start justify-between gap-4 border-b p-5' }}">
                    <div>
                        <p class="{{ $isPublic ? 'text-xs font-bold uppercase tracking-[0.24em] text-blue-700' : 'text-xs font-bold uppercase tracking-[0.24em] bo-text--info' }}">Detalle de sector</p>
                        <h3 class="{{ $isPublic ? 'mt-2 text-2xl font-black text-slate-950' : 'mt-2 text-2xl font-black bo-strong' }}">{{ $selectedSector?->nombre ?? $selectedHotspot['label'] }}</h3>
                        <p class="{{ $isPublic ? 'mt-1 text-sm text-slate-600' : 'mt-1 text-sm bo-muted' }}">Identificador: {{ $selectedSector?->identificador_mapa ?? $selectedHotspot['identificador'] }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($selectedSector && ! $editingSectorId)
                            @can('servicios.editar')
                                <button type="button" wire:click="editSector({{ $selectedSector->id }})" class="{{ $isPublic ? 'rounded-full bg-blue-700 px-3 py-1 text-sm font-bold text-white transition hover:bg-blue-800' : 'rounded-full bg-blue-700 px-3 py-1 text-sm font-bold text-white transition' }}">
                                    Editar nombre
                                </button>
                            @endcan
                        @endif
                        <button type="button" wire:click="closeSectorDetail" class="{{ $isPublic ? 'rounded-full border border-slate-300 px-3 py-1 text-sm font-bold text-slate-600 transition hover:bg-slate-50' : 'rounded-full border px-3 py-1 text-sm font-bold transition bo-panel-subtle bo-muted' }}">
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-5">
                    <x-flash-notifications />

                    @if ($editingSectorId)
                        <form wire:submit="saveSector" class="{{ $isPublic ? 'mb-4 rounded-2xl border border-blue-200 bg-blue-50 p-4' : 'mb-4 rounded-2xl border p-4 bo-panel-subtle' }}">
                            <label class="{{ $isPublic ? 'block text-sm font-bold text-blue-950' : 'block text-sm font-bold bo-text--info' }}" for="sector-nombre">Nombre del sector</label>
                            <input id="sector-nombre" type="text" wire:model="sectorNombre" maxlength="255" class="{{ $isPublic ? 'mt-2 w-full rounded-xl border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500' : 'mt-2 w-full rounded-xl text-sm' }}">
                            @error('sectorNombre') <p class="{{ $isPublic ? 'mt-1 text-xs text-red-600' : 'mt-1 text-xs bo-text--danger' }}">{{ $message }}</p> @enderror
                            <div class="mt-3 flex gap-2">
                                <button type="submit" class="{{ $isPublic ? 'rounded-xl bg-blue-700 px-3 py-2 text-sm font-bold text-white hover:bg-blue-800' : 'rounded-xl bg-blue-700 px-3 py-2 text-sm font-bold text-white' }}">Guardar</button>
                                <button type="button" wire:click="cancelSectorEdit" class="{{ $isPublic ? 'rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold text-slate-600 hover:bg-white' : 'rounded-xl border px-3 py-2 text-sm font-bold bo-panel-subtle bo-muted' }}">Cancelar</button>
                            </div>
                        </form>
                    @endif

                    @if ($selectedSector)
                        <div class="{{ $isPublic ? 'mb-4 rounded-2xl bg-blue-50 p-4 text-sm font-semibold text-blue-900' : 'mb-4 rounded-2xl p-4 text-sm font-semibold bo-panel-subtle bo-text--info' }}">
                            @if ($searchTerm !== '')
                                {{ count($matchingActivoIds) }} de {{ $selectedSector->activos->count() }} activos coinciden con la búsqueda
                            @else
                                {{ $selectedSector->activos->count() }} {{ $selectedSector->activos->count() === 1 ? 'activo registrado' : 'activos registrados' }} en este sector.
                            @endif
                        </div>

                        @unless ($isPublic)
                            <div class="{{ $isPublic ? 'mb-4 rounded-2xl bg-emerald-50 p-4' : 'mb-4 rounded-2xl p-4 bo-panel-subtle' }}">
                                <p class="{{ $isPublic ? 'text-sm font-semibold text-emerald-950' : 'text-sm font-semibold' }}">{{ $selectedSector->insumo_existencias_sum_cantidad ?? 0 }} unidades de insumos en esta bodega.</p>
                                <div class="mt-3 space-y-2">
                                    @forelse ($selectedSector->insumoExistencias as $existencia)
                                        <p class="{{ $isPublic ? 'text-sm text-emerald-900' : 'text-sm' }}" @if ($searchTerm !== '' && (mb_stripos($existencia->insumo->codigo, $searchTerm) !== false || mb_stripos($existencia->insumo->nombre, $searchTerm) !== false)) style="border: 2px solid #67e8f9; padding: 4px;" @endif>@foreach ($this->searchSegments($existencia->insumo->codigo, $searchTerm) as [$segment, $match])@if ($match)<mark>{{ $segment }}</mark>@endif@unless ($match){{ $segment }}@endunless@endforeach · @foreach ($this->searchSegments($existencia->insumo->nombre, $searchTerm) as [$segment, $match])@if ($match)<mark>{{ $segment }}</mark>@endif@unless ($match){{ $segment }}@endunless@endforeach: {{ $existencia->cantidad }} {{ $existencia->insumo->unidad_medida }}
                                            @if ($searchTerm !== '' && (mb_stripos($existencia->insumo->codigo, $searchTerm) !== false || mb_stripos($existencia->insumo->nombre, $searchTerm) !== false)) <span>Coincide con la búsqueda</span> @endif
                                        </p>
                                    @empty
                                        <p class="{{ $isPublic ? 'text-sm text-emerald-800' : 'text-sm' }}">No hay insumos almacenados en este sector.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endunless

                        <div class="space-y-3">
                            @forelse ($selectedSector->activos as $activo)
                                <article wire:key="sector-activo-{{ $activo->id }}" class="{{ $isPublic ? 'rounded-2xl border border-slate-200 bg-slate-50 p-4' : 'rounded-2xl border p-4 bo-panel-subtle' }}" @if (in_array($activo->id, $matchingActivoIds)) style="border: 2px solid #0891b2; box-shadow: 0 0 0 3px #a5f3fc;" @endif>
                                    @if (in_array($activo->id, $matchingActivoIds)) <span class="text-xs font-semibold">Coincide con la búsqueda</span> @endif
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="{{ $isPublic ? 'font-bold text-slate-950' : 'font-bold bo-strong' }}">@foreach ($this->searchSegments($activo->nombre, $searchTerm) as [$segment, $match])@if ($match)<mark>{{ $segment }}</mark>@endif@unless ($match){{ $segment }}@endunless@endforeach</p>
                                            <p class="{{ $isPublic ? 'mt-1 text-xs text-slate-600' : 'mt-1 text-xs bo-muted' }}">@foreach ($this->searchSegments($activo->codigo_inventario ?: 'Sin codigo inventario', $searchTerm) as [$segment, $match])@if ($match)<mark>{{ $segment }}</mark>@endif@unless ($match){{ $segment }}@endunless@endforeach</p>
                                        </div>
                                        <span class="{{ $isPublic ? 'rounded-full bg-white px-2 py-1 text-xs font-bold text-slate-700 shadow-sm' : 'rounded-full px-2 py-1 text-xs font-bold bo-panel-subtle bo-strong' }}">{{ $activo->estado }}</span>
                                    </div>

                                    @if ($activo->numero_serie || (! $isPublic && $activo->observaciones))
                                        <div class="{{ $isPublic ? 'mt-3 space-y-1 text-xs text-slate-600' : 'mt-3 space-y-1 text-xs bo-muted' }}">
                                            @if ($activo->numero_serie)
                                                <p>@if ($searchTerm === '')Serie: {{ $activo->numero_serie }}@else Serie: @foreach ($this->searchSegments($activo->numero_serie, $searchTerm) as [$segment, $match])@if ($match)<mark>{{ $segment }}</mark>@endif@unless ($match){{ $segment }}@endunless@endforeach @endif</p>
                                            @endif
                                            @if (! $isPublic && $activo->observaciones)
                                                <p>{{ $activo->observaciones }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="{{ $isPublic ? 'rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500' : 'rounded-2xl border border-dashed p-5 text-sm bo-muted' }}">
                                    Este sector no tiene activos registrados.
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="{{ $isPublic ? 'rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500' : 'rounded-2xl border border-dashed p-5 text-sm bo-muted' }}">
                            Este hotspot existe en el plano, pero no tiene sector asociado en el inventario.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
