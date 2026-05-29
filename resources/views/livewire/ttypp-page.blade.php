<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">TTyPP - Unidad de Testigos y Peritos</h2>
</x-slot:header>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    @if (session('ok'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
            <p class="text-sm font-medium text-blue-700">Registros almacenados</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ number_format($totalRegistros) }}</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-medium text-amber-700">Estado de implementacion</p>
            <p class="mt-2 text-lg font-semibold text-amber-900">Formulario en desarrollo</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <label for="ttypp-search" class="text-sm font-medium text-slate-700">Buscar</label>
                <input
                    id="ttypp-search"
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por RIT, RUC, Acta o Sala"
                    class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>
        </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Automatizacion inicial</h2>
        <p class="mt-1 text-sm text-slate-600">Importa CSV para carga masiva o exporta el listado filtrado.</p>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            @can('ttypp.importar')
                <form wire:submit="importFromCsv" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Archivo CSV</label>
                        <input type="file" wire:model="importCsv" accept=".csv,text/csv,.txt" class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        @error('importCsv') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Importar CSV</button>
                </form>
            @endcan

            @can('ttypp.exportar')
                <button type="button" wire:click="exportCsv" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Exportar CSV</button>
            @endcan
        </div>
    </div>

    <form wire:submit="save" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Editar registro' : 'Nuevo registro' }}</h2>
            @if ($editingId)
                <button
                    type="button"
                    wire:click="cancelEdit"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Cancelar edicion
                </button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-sm font-medium text-slate-700">RIT</label>
                <input type="text" wire:model.live.debounce.300ms="rit" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('rit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">RUC</label>
                <input type="text" wire:model="ruc" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('ruc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Tipo AUD</label>
                <input type="text" wire:model="tipo_audiencia" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('tipo_audiencia') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">N Dias</label>
                <input type="text" wire:model="numero_dias" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('numero_dias') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Cantidad TTyPP</label>
                <input type="text" wire:model="cantidad" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('cantidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Modalidad</label>
                <input type="text" wire:model="modalidad" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('modalidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Encargada TI MP</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_ti_mp" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('encargada_ti_mp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Encargada TI Defensa</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_ti_d" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('encargada_ti_d') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Encargada Zoom</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_zoom" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('encargada_zoom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Acta</label>
                <input type="text" list="ttypp-acta-sala-causa" wire:model="acta" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('acta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Sala</label>
                <input type="text" wire:model="sala" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('sala') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Fecha de asignacion</label>
                <input type="date" wire:model="fecha_asignacion" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_asignacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-5">
            <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                {{ $editingId ? 'Guardar cambios' : 'Crear registro' }}
            </button>
        </div>

        <datalist id="ttypp-encargadas">
            @foreach ($encargadasTtypp as $nombreEncargada)
                <option value="{{ $nombreEncargada }}"></option>
            @endforeach
        </datalist>

        <datalist id="ttypp-acta-sala-causa">
            @foreach ($actasSalaCausa as $nombreSalaCausa)
                <option value="{{ $nombreSalaCausa }}"></option>
            @endforeach
        </datalist>

    </form>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 grid gap-4 lg:grid-cols-2 lg:items-start">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Listado TTYPP</h2>
                <p class="mt-1 text-sm text-slate-600">Se muestran los registros del dia seleccionado.</p>
                <p class="mt-2 text-sm text-slate-700">Total del dia: <span class="font-semibold">{{ number_format($totalRegistrosDia) }}</span></p>
            </div>

            <div class="justify-self-end w-full max-w-xl rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="previousDay" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Anterior</button>
                    <input type="date" wire:model.defer="selectedDate" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" wire:click="applySelectedDate" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Ver</button>
                    <button type="button" wire:click="today" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Hoy</button>
                    <button type="button" wire:click="nextDay" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Siguiente</button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">RIT</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Tipo AUD</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">N Dias</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Cant.</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Modalidad</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">TI MP</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">TI D</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Zoom</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">RUC</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Acta</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Sala</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Jueces</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Fecha Asignacion</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($registros as $registro)
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->rit ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->tipo_audiencia ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->numero_dias ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->cantidad ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->modalidad ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_ti_mp ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_ti_d ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->encargada_zoom ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->ruc ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->acta ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->sala ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $registro->jueces ?? '-' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ optional($registro->fecha_asignacion)->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    @can('ttypp.editar')
                                        <button type="button" wire:click="edit({{ $registro->id }})" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</button>
                                        <button type="button" wire:click="delete({{ $registro->id }})" wire:confirm="¿Eliminar registro?" class="rounded-md border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                                    @else
                                        <span class="text-xs text-slate-500">Solo lectura</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-3 py-6 text-center text-sm text-slate-500">No hay registros para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $registros->links() }}
        </div>
    </div>
    </div>
</div>
