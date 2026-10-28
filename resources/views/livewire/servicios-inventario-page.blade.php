<x-slot:header>
    <x-page-heading eyebrow="Servicios · Inventario" title="Inventario de servicios" />
</x-slot:header>

<div class="py-2">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card">
                <p class="text-xs font-semibold uppercase tracking-wider bo-muted">Activos registrados</p>
                <p class="mt-1 text-3xl font-bold bo-strong">{{ number_format($totalActivos) }}</p>
            </div>
            <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card">
                <p class="text-xs font-semibold uppercase tracking-wider bo-muted">Sectores disponibles</p>
                <p class="mt-1 text-3xl font-bold bo-strong">{{ number_format($sectores->count()) }}</p>
            </div>
            <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card">
                <p class="text-xs font-semibold uppercase tracking-wider bo-muted">Filtro actual</p>
                <p class="mt-2 text-sm font-medium bo-strong">{{ $estadoFiltro ?: 'Todos los estados' }}</p>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('backoffice.servicios.mapa') }}" wire:navigate class="rounded-md border bo-border bo-panel-subtle px-3 py-2 text-sm font-medium bo-text--info">
                Abrir mapa interactivo
            </a>
        </div>

        @canany(['servicios.crear', 'servicios.editar'])
            <form wire:submit="save" class="rounded-xl border bo-border bo-panel p-5 shadow-sm bo-card">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold bo-strong">{{ $editingId ? 'Editar activo' : 'Nuevo activo' }}</h3>
                        <p class="mt-1 text-sm bo-muted">Registra bienes e insumos asociados a un sector del piso.</p>
                    </div>
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">
                            Cancelar edicion
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium bo-strong">Nombre</label>
                        <input type="text" wire:model="nombre" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                        @error('nombre') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium bo-strong">Sector</label>
                        <div class="mt-1 flex bo-segmented p-1">
                            @foreach ([7 => 'Piso 7', 8 => 'Piso 8', 9 => 'Piso 9', 0 => 'Subterráneo'] as $floor => $label)
                                <button type="button" wire:click="selectPiso({{ $floor }})" class="flex-1 bo-segmented-item px-3 py-1.5 text-sm font-semibold transition {{ $piso === $floor ? 'is-active' : '' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <select wire:model="sector_id" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-select">
                            <option value="">Seleccione sector</option>
                            @foreach ($sectoresPiso as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sector_id') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium bo-strong">Estado</label>
                        <select wire:model="estado" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-select">
                            @foreach ($estados as $estadoOption)
                                <option value="{{ $estadoOption }}">{{ $estadoOption }}</option>
                            @endforeach
                        </select>
                        @error('estado') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium bo-strong">Código inventario</label>
                        <input type="text" wire:model="codigo_inventario" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                        @error('codigo_inventario') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium bo-strong">Número de serie</label>
                        <input type="text" wire:model="numero_serie" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                        @error('numero_serie') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="text-sm font-medium bo-strong">Observaciones</label>
                        <textarea wire:model="observaciones" rows="3" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input"></textarea>
                        @error('observaciones') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="rounded-md px-4 py-2 text-sm font-medium bo-btn bo-btn--primary">
                        {{ $editingId ? 'Guardar cambios' : 'Crear activo' }}
                    </button>
                </div>
            </form>
        @endcanany

        <div class="rounded-xl border bo-border bo-panel p-5 shadow-sm bo-card">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold bo-strong">Listado de activos</h3>
                        <p class="mt-1 text-sm bo-muted">Busca por nombre, codigo, serie o sector.</p>
                    </div>
                    <a href="{{ route('backoffice.servicios.inventario.exportar') }}" class="inline-flex items-center justify-center" title="Exportar Excel" aria-label="Exportar Excel">
                        <i class="fas fa-file-excel inline-block" style="color:var(--bo-ok); font-size:32px;" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:w-2/3">
                    <div>
                        <label class="text-sm font-medium bo-strong">Busqueda</label>
                        <input type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input" placeholder="Inventario, nombre o sector">
                    </div>
                    <div>
                        <label class="text-sm font-medium bo-strong">Estado</label>
                        <select wire:model.live="estadoFiltro" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-select">
                            <option value="">Todos</option>
                            @foreach ($estados as $estadoOption)
                                <option value="{{ $estadoOption }}">{{ $estadoOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium bo-strong">Sector</label>
                        <select wire:model.live="sectorFiltro" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-select">
                            <option value="">Todos</option>
                            @foreach ($sectores as $sector)
                                <option value="{{ $sector->id }}">Piso {{ (int) $sector->piso }} · {{ $sector->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm bo-table">
                    <thead class="bo-panel-subtle">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Activo</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Inventario</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Serie</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Sector</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Estado</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Observaciones</th>
                            <th class="px-3 py-2 text-left font-semibold bo-strong">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bo-panel">
                        @forelse ($activos as $activo)
                            <tr wire:key="activo-{{ $activo->id }}">
                                <td class="px-3 py-2 font-medium bo-strong">{{ $activo->nombre }}</td>
                                <td class="px-3 py-2 bo-strong">{{ $activo->codigo_inventario ?: '-' }}</td>
                                <td class="px-3 py-2 bo-strong">{{ $activo->numero_serie ?: '-' }}</td>
                                <td class="px-3 py-2 bo-strong">{{ $activo->sector?->nombre ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full bo-panel-subtle px-2 py-1 text-xs font-semibold bo-strong">{{ $activo->estado }}</span>
                                </td>
                                <td class="max-w-xs px-3 py-2 bo-muted">{{ $activo->observaciones ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        @can('servicios.editar')
                                            <button type="button" wire:click="edit({{ $activo->id }})" class="rounded-md border bo-border px-3 py-1 text-xs font-medium bo-text--info ">
                                                Editar
                                            </button>
                                        @endcan
                                        @can('servicios.eliminar')
                                            <button type="button" wire:click="delete({{ $activo->id }})" wire:confirm="Eliminar activo del inventario?" class="rounded-md border bo-border px-3 py-1 text-xs font-medium bo-text--danger ">
                                                Eliminar
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm bo-muted">No hay activos para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $activos->links() }}
            </div>
        </div>
    </div>
</div>
