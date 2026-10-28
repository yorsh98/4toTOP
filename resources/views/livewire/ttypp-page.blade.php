<x-slot:header>
    <x-page-heading eyebrow="TTYPP · Gestión operativa" title="Unidad de Testigos y Peritos" />
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">

    <x-flash-notifications />

    <!-- Dashboard Operativo -->
    <div class="grid gap-4 sm:grid-cols-3">
        <!-- Tarjeta 1: Total registros -->
        <div class="bo-kpi flex items-center justify-between gap-3 min-w-0">
            <div>
                <p class="text-xs font-semibold bo-muted uppercase tracking-wider">Registros del Día</p>
                <h3 class="text-2xl font-bold bo-strong mt-1">{{ $totalRegistrosDia }}</h3>
            </div>
            <div class="rounded-full bo-panel-subtle p-3 bo-text--info">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
        </div>
        
        <!-- Tarjeta 2: Modalidades -->
        <div class="bo-kpi flex items-center justify-between gap-3 min-w-0">
            <div>
                <p class="text-xs font-semibold bo-muted uppercase tracking-wider">Modalidades Activas</p>
                <div class="text-sm font-semibold bo-strong mt-1">
                    @forelse($modalidadConteo as $mod => $cnt)
                        <span class="inline-block bo-panel-subtle rounded px-2 py-0.5 text-xs mr-1 my-0.5 bo-strong">{{ $mod ?: 'Sin definir' }}: {{ $cnt }}</span>
                    @empty
                        <span class="bo-muted text-xs">Sin registros hoy</span>
                    @endforelse
                </div>
            </div>
            <div class="rounded-full bo-panel-subtle p-3 bo-text--info">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            </div>
        </div>

        <!-- Tarjeta 3: Control de TTyPP -->
        <div class="bo-kpi flex items-center justify-between gap-3 min-w-0">
            <div>
                <p class="text-xs font-semibold bo-muted uppercase tracking-wider">Control de TTyPP</p>
                <div class="flex flex-wrap items-center gap-3 mt-1">
                    <span class="text-sm font-semibold bo-text--ok bo-panel-subtle px-2 py-0.5 rounded">Completas: {{ $ttyppAsignadas }}</span>
                    <span class="text-sm font-semibold bo-text--warn bo-panel-subtle px-2 py-0.5 rounded">Pendientes: {{ $ttyppPendientes }}</span>
                </div>
            </div>
            <div class="rounded-full bo-panel-subtle p-3 bo-text--ok">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- Bloque superior de resumen/busqueda retirado visualmente; se mantiene la logica de filtro en Livewire para uso interno/exportaciones. --}}

    <div class="rounded-xl border bo-border bo-panel p-5 shadow-sm bo-card">
        <h2 class="text-lg font-semibold bo-strong">Automatización inicial</h2>
        <p class="mt-1 text-sm bo-muted">Importa Excel desde SIAGJ o exporta el listado filtrado.</p>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            @can('ttypp.importar')
                <form wire:submit="importFromExcel" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div>
                        <label class="text-sm font-medium bo-strong">Archivo Excel SIAGJ</label>
                        <input type="file" wire:model="importExcel" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="mt-1 block w-full text-sm bo-strong file:mr-3 file:rounded-md file:px-3 file:py-2 file:font-medium bo-input">
                        @error('importExcel') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md px-4 py-2 text-sm font-medium bo-btn bo-btn--primary">Importar Excel</button>
                </form>
            @endcan

            @can('ttypp.exportar')
                <button type="button" wire:click="exportExcel" class="rounded-md px-4 py-2 text-sm font-medium bo-btn bo-btn--primary">Exportar Excel</button>
            @endcan
        </div>
    </div>

    <form wire:submit="save" class="rounded-xl border bo-border bo-panel p-5 shadow-sm bo-card">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold bo-strong">{{ $editingId ? 'Editar registro' : 'Nuevo registro' }}</h2>
            @if ($editingId)
                <button
                    type="button"
                    wire:click="cancelEdit"
                    class="rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary"
                >
                    Cancelar edicion
                </button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-sm font-medium bo-strong">RIT</label>
                <input type="text" wire:model.live.debounce.300ms="rit" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('rit') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">RUC</label>
                <input type="text" wire:model="ruc" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('ruc') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Tipo AUD</label>
                <input type="text" wire:model="tipo_audiencia" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('tipo_audiencia') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">N° días</label>
                <input type="text" wire:model="numero_dias" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('numero_dias') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Cantidad TTyPP</label>
                <input type="text" wire:model="cantidad" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('cantidad') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Modalidad</label>
                <input type="text" list="ttypp-modalidades" wire:model="modalidad" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('modalidad') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Encargada TI MP</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_ti_mp" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('encargada_ti_mp') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Encargada TI Defensa</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_ti_d" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('encargada_ti_d') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Encargada Zoom</label>
                <input type="text" list="ttypp-encargadas" wire:model="encargada_zoom" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('encargada_zoom') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Acta</label>
                <input type="text" list="ttypp-acta-sala-causa" wire:model="acta" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('acta') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Sala</label>
                <input type="text" wire:model="sala" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('sala') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium bo-strong">Fecha de asignación</label>
                <input type="date" wire:model="fecha_asignacion" class="mt-1 w-full rounded-md bo-border text-sm shadow-sm bo-input">
                @error('fecha_asignacion') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($editingId && !empty($selectedRegistroHistorial))
            <div class="mt-6 border-t bo-border pt-5">
                <h3 class="text-sm font-semibold bo-strong mb-3 flex items-center gap-1.5">
                    <svg class="h-4 w-4 bo-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Historial de modificaciones del Registro
                </h3>
                <div class="bo-panel-subtle rounded-lg p-4 max-h-48 overflow-y-auto space-y-3">
                    @foreach ($selectedRegistroHistorial as $log)
                        <div class="text-xs border-l-2 bo-border pl-3 py-1">
                            <div class="flex items-center justify-between bo-muted font-medium">
                                <span>Por: {{ $log['user']['name'] ?? 'Usuario del sistema' }}</span>
                                <span>{{ \Carbon\Carbon::parse($log['created_at'])->timezone('America/Santiago')->format('d/m/Y H:i:s') }}</span>
                            </div>
                            <div class="mt-1 space-y-1 bo-strong">
                                @foreach ($log['cambios'] as $campo => $change)
                                    <div>
                                        <span class="font-semibold bo-muted">{{ ucwords(str_replace('_', ' ', $campo)) }}:</span>
                                        <span class="bo-text--danger line-through bo-panel-subtle px-1 rounded">{{ $change['old'] ?: 'vacío' }}</span>
                                        <span class="mx-1">→</span>
                                        <span class="bo-text--ok bo-panel-subtle px-1 rounded font-medium">{{ $change['new'] ?: 'vacío' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5">
            <button type="submit" class="rounded-md px-4 py-2 text-sm font-medium bo-btn bo-btn--primary">
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

    <div class="rounded-xl border bo-border bo-panel p-5 shadow-sm bo-card">
        <div class="mb-4 flex flex-col xl:flex-row xl:items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold bo-strong">Listado TTYPP</h2>
                <p class="mt-1 text-sm bo-muted">Se muestran los registros del dia seleccionado.</p>
                <p class="mt-2 text-sm bo-strong">Total del dia: <span class="font-semibold">{{ number_format($totalRegistrosDia) }}</span></p>
            </div>

            <div class="w-full xl:w-auto shrink-0 rounded-lg border bo-border bo-panel-subtle p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="previousDay" class="flex-1 sm:flex-none justify-center rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">Anterior</button>
                    <input type="date" wire:model.defer="selectedDate" class="flex-1 sm:flex-none rounded-md bo-border text-sm shadow-sm bo-input">
                    <button type="button" wire:click="applySelectedDate" class="flex-1 sm:flex-none justify-center rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">Ver</button>
                    <button type="button" wire:click="today" class="flex-1 sm:flex-none justify-center rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">Hoy</button>
                    <button type="button" wire:click="nextDay" class="flex-1 sm:flex-none justify-center rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">Siguiente</button>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @can('ttypp.mail.preview')
                        <button type="button" wire:click="previewTtyppMail" class="flex-1 sm:flex-none justify-center rounded-md border bo-border px-3 py-2 text-sm font-medium bo-btn bo-btn--secondary">Previsualizar correo</button>
                    @endcan
                    @can('ttypp.mail.enviar')
                        <button type="button" wire:click="sendTtyppMail" wire:confirm="¿Remitir correo TTyPP para la fecha seleccionada?" class="flex-1 sm:flex-none justify-center rounded-md px-3 py-2 text-sm font-medium bo-btn bo-btn--primary">Remitir correo</button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm bo-table">
                <thead class="bo-panel-subtle">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">RIT</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Tipo AUD</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">N° días</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Cant.</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Modalidad</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">TI MP</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">TI D</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Zoom</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">RUC</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Acta</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Sala</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Jueces</th>
                        <th class="px-3 py-2 text-left font-semibold bo-strong">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bo-panel">
                    @forelse ($registros as $registro)
                        <tr>
                            <td class="px-3 py-2 bo-strong">{{ $registro->rit ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->tipo_audiencia ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->numero_dias ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->cantidad ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->modalidad ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->encargada_ti_mp ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->encargada_ti_d ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->encargada_zoom ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->ruc ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->acta ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->sala ?? '-' }}</td>
                            <td class="px-3 py-2 bo-strong">{{ $registro->jueces ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    @can('ttypp.editar')
                                        <button type="button" wire:click="edit({{ $registro->id }})" class="rounded-md border bo-border px-2 py-1 text-xs font-medium bo-strong ">Editar</button>
                                        <button type="button" wire:click="delete({{ $registro->id }})" wire:confirm="¿Eliminar registro?" class="rounded-md border bo-border px-2 py-1 text-xs font-medium bo-text--danger ">Eliminar</button>
                                    @else
                                        <span class="text-xs bo-muted">Solo lectura</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-3 py-6 text-center text-sm bo-muted">No hay registros para mostrar.</td>
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
