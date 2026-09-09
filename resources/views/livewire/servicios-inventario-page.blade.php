<x-slot:header>
    <x-page-heading eyebrow="Servicios · Inventario" title="Inventario de servicios" />
</x-slot:header>

<div class="py-2">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Activos registrados</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalActivos) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sectores disponibles</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($sectores->count()) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Filtro actual</p>
                <p class="mt-2 text-sm font-medium text-slate-700">{{ $estadoFiltro ?: 'Todos los estados' }}</p>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('backoffice.servicios.mapa') }}" wire:navigate class="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                Abrir mapa interactivo
            </a>
        </div>

        @canany(['servicios.crear', 'servicios.editar'])
            <form wire:submit="save" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Editar activo' : 'Nuevo activo' }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Registra bienes e insumos asociados a un sector del piso.</p>
                    </div>
                    @if ($editingId)
                        <button type="button" wire:click="resetForm" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar edicion
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nombre</label>
                        <input type="text" wire:model="nombre" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Sector</label>
                        <div class="mt-1 flex rounded-lg bg-slate-100 p-1">
                            @foreach ([7 => 'Piso 7', 8 => 'Piso 8', 9 => 'Piso 9', 0 => 'Subterráneo'] as $floor => $label)
                                <button type="button" wire:click="selectPiso({{ $floor }})" class="flex-1 rounded-md px-3 py-1.5 text-sm font-semibold transition {{ $piso === $floor ? 'bg-blue-700 text-white shadow' : 'text-slate-600 hover:bg-white' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <select wire:model="sector_id" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione sector</option>
                            @foreach ($sectoresPiso as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sector_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Estado</label>
                        <select wire:model="estado" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($estados as $estadoOption)
                                <option value="{{ $estadoOption }}">{{ $estadoOption }}</option>
                            @endforeach
                        </select>
                        @error('estado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Codigo inventario</label>
                        <input type="text" wire:model="codigo_inventario" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('codigo_inventario') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Numero serie</label>
                        <input type="text" wire:model="numero_serie" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('numero_serie') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="text-sm font-medium text-slate-700">Observaciones</label>
                        <textarea wire:model="observaciones" rows="3" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('observaciones') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        {{ $editingId ? 'Guardar cambios' : 'Crear activo' }}
                    </button>
                </div>
            </form>
        @endcanany

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Listado de activos</h3>
                        <p class="mt-1 text-sm text-slate-600">Busca por nombre, codigo, serie o sector.</p>
                    </div>
                    <a href="{{ route('backoffice.servicios.inventario.exportar') }}" class="inline-flex items-center justify-center" title="Exportar Excel" aria-label="Exportar Excel">
                        <i class="fas fa-file-excel inline-block" style="color:#217346; font-size:32px;" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:w-2/3">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Busqueda</label>
                        <input type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Inventario, nombre o sector">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Estado</label>
                        <select wire:model.live="estadoFiltro" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todos</option>
                            @foreach ($estados as $estadoOption)
                                <option value="{{ $estadoOption }}">{{ $estadoOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Sector</label>
                        <select wire:model.live="sectorFiltro" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todos</option>
                            @foreach ($sectores as $sector)
                                <option value="{{ $sector->id }}">Piso {{ (int) $sector->piso }} · {{ $sector->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Activo</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Inventario</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Serie</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Sector</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Estado</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Observaciones</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($activos as $activo)
                            <tr wire:key="activo-{{ $activo->id }}">
                                <td class="px-3 py-2 font-medium text-slate-900">{{ $activo->nombre }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->codigo_inventario ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->numero_serie ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $activo->sector?->nombre ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $activo->estado }}</span>
                                </td>
                                <td class="max-w-xs px-3 py-2 text-slate-600">{{ $activo->observaciones ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        @can('servicios.editar')
                                            <button type="button" wire:click="edit({{ $activo->id }})" class="rounded-md border border-blue-200 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50">
                                                Editar
                                            </button>
                                        @endcan
                                        @can('servicios.eliminar')
                                            <button type="button" wire:click="delete({{ $activo->id }})" wire:confirm="Eliminar activo del inventario?" class="rounded-md border border-red-200 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-50">
                                                Eliminar
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No hay activos para los filtros seleccionados.</td>
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
