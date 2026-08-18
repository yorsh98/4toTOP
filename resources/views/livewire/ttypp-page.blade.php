<x-slot:header>
    <x-page-heading eyebrow="TTYPP · Gestion operativa" title="Unidad de Testigos y Peritos" />
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">

    <x-flash-notifications />

    <!-- Dashboard Operativo -->
    <div class="grid gap-4 sm:grid-cols-3">
        <!-- Tarjeta 1: Total registros -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Registros del Día</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $totalRegistrosDia }}</h3>
            </div>
            <div class="rounded-full bg-blue-50 p-3 text-blue-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
        </div>
        
        <!-- Tarjeta 2: Modalidades -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Modalidades Activas</p>
                <div class="text-sm font-semibold text-slate-800 mt-1">
                    @forelse($modalidadConteo as $mod => $cnt)
                        <span class="inline-block bg-slate-100 rounded px-2 py-0.5 text-xs mr-1 my-0.5 text-slate-700">{{ $mod ?: 'Sin definir' }}: {{ $cnt }}</span>
                    @empty
                        <span class="text-slate-400 text-xs">Sin registros hoy</span>
                    @endforelse
                </div>
            </div>
            <div class="rounded-full bg-violet-50 p-3 text-violet-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            </div>
        </div>

        <!-- Tarjeta 3: Control de TTyPP -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Control de TTyPP</p>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-sm font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Completas: {{ $ttyppAsignadas }}</span>
                    <span class="text-sm font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded">Pendientes: {{ $ttyppPendientes }}</span>
                </div>
            </div>
            <div class="rounded-full bg-emerald-50 p-3 text-emerald-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- Bloque superior de resumen/busqueda retirado visualmente; se mantiene la logica de filtro en Livewire para uso interno/exportaciones. --}}

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Automatizacion inicial</h2>
        <p class="mt-1 text-sm text-slate-600">Importa Excel desde SIAGJ o exporta el listado filtrado.</p>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            @can('ttypp.importar')
                <form wire:submit="importFromExcel" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Archivo Excel SIAGJ</label>
                        <input type="file" wire:model="importExcel" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        @error('importExcel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Importar Excel</button>
                </form>
            @endcan

            @can('ttypp.exportar')
                <button type="button" wire:click="exportExcel" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Exportar Excel</button>
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
                <input type="text" list="ttypp-modalidades" wire:model="modalidad" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
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

        @if ($editingId && !empty($selectedRegistroHistorial))
            <div class="mt-6 border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-800 mb-3 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Historial de modificaciones del Registro
                </h3>
                <div class="bg-slate-50 rounded-lg p-4 max-h-48 overflow-y-auto space-y-3">
                    @foreach ($selectedRegistroHistorial as $log)
                        <div class="text-xs border-l-2 border-blue-500 pl-3 py-1">
                            <div class="flex items-center justify-between text-slate-500 font-medium">
                                <span>Por: {{ $log['user']['name'] ?? 'Usuario del sistema' }}</span>
                                <span>{{ \Carbon\Carbon::parse($log['created_at'])->timezone('America/Santiago')->format('d/m/Y H:i:s') }}</span>
                            </div>
                            <div class="mt-1 space-y-1 text-slate-700">
                                @foreach ($log['cambios'] as $campo => $change)
                                    <div>
                                        <span class="font-semibold text-slate-600">{{ ucwords(str_replace('_', ' ', $campo)) }}:</span>
                                        <span class="text-red-600 line-through bg-red-50 px-1 rounded">{{ $change['old'] ?: 'vacío' }}</span>
                                        <span class="mx-1">→</span>
                                        <span class="text-emerald-700 bg-emerald-50 px-1 rounded font-medium">{{ $change['new'] ?: 'vacío' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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

        <datalist id="ttypp-modalidades">
            @foreach ($modalidades as $mod)
                <option value="{{ $mod }}"></option>
            @endforeach
        </datalist>

    </form>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col xl:flex-row xl:items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Listado TTYPP</h2>
                <p class="mt-1 text-sm text-slate-600">Se muestran los registros del dia seleccionado.</p>
                <p class="mt-2 text-sm text-slate-700">Total del dia: <span class="font-semibold">{{ number_format($totalRegistrosDia) }}</span></p>
            </div>

            <div class="w-full xl:w-auto shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="previousDay" class="flex-1 sm:flex-none justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Anterior</button>
                    <input type="date" wire:model.defer="selectedDate" class="flex-1 sm:flex-none rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" wire:click="applySelectedDate" class="flex-1 sm:flex-none justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Ver</button>
                    <button type="button" wire:click="today" class="flex-1 sm:flex-none justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Hoy</button>
                    <button type="button" wire:click="nextDay" class="flex-1 sm:flex-none justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Siguiente</button>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @can('ttypp.mail.preview')
                        <button type="button" wire:click="previewTtyppMail" class="flex-1 sm:flex-none justify-center rounded-md border border-blue-300 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Previsualizar correo</button>
                    @endcan
                    @can('ttypp.mail.enviar')
                        <button type="button" wire:click="sendTtyppMail" wire:confirm="¿Remitir correo TTyPP para la fecha seleccionada?" class="flex-1 sm:flex-none justify-center rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">Remitir correo</button>
                    @endcan
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
                            <td colspan="13" class="px-3 py-6 text-center text-sm text-slate-500">No hay registros para mostrar.</td>
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
