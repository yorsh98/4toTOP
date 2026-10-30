<div>
    <div class="max-w-6xl mx-auto p-6 bo-panel">
    <h2 class="text-2xl font-bold bo-strong mb-6">Audiencias Cortas</h2>

    <div class="rounded border text-sm mb-4 text-center bo-alert bo-alert--warn px-2 py-2">
        <span>Los datos <b>COMPARTIDOS</b> se aplican a todas las audiencias a registrar</span>
    </div>

    <form
        x-data
        x-on:submit.prevent="
            const f = $el;
            const firstInvalid = f.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.reportValidity();
                return;
            }
            $wire.guardar();
        "
        class="space-y-8"
    >
        <!-- Datos compartidos -->
        <div class="bo-panel-subtle p-4 border rounded-lg">
            <h3 class="text-lg font-medium bo-strong mb-4">Datos compartidos</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Fecha*</label>
                    <input type="date" wire:model.defer="shared.fecha" required class="bo-input w-full">
                    @error('shared.fecha') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Hora inicio*</label>
                    <input type="time" wire:model.defer="shared.hora_inicio" required class="bo-input w-full">
                    @error("shared.hora_inicio") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Presidente*</label>
                    <input type="text" wire:model.defer="shared.JuezP" required list="juecesSharedList" class="bo-input w-full">
                    @error('shared.JuezP') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Redactor*</label>
                    <input type="text" wire:model.defer="shared.JuezR" required list="juecesSharedList" class="bo-input w-full">
                    @error('shared.JuezR') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Integrante*</label>
                    <input type="text" wire:model.defer="shared.JuezI" required list="juecesSharedList" class="bo-input w-full">
                    @error('shared.JuezI') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Sala*</label>
                    <input type="text" wire:model.defer="shared.sala" required class="bo-input w-full">
                    @error('shared.sala') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Ubicación*</label>
                    <input type="text" wire:model.defer="shared.ubicacion" required class="bo-input w-full">
                    @error("shared.ubicacion") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Anfitrión</label>
                    <input type="text" wire:model.defer="shared.anfitrion" list="actaAnfitrionSharedList" class="bo-input w-full">
                    @error('shared.anfitrion') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="bo-label block text-sm font-medium bo-strong mb-1">Cuenta Zoom</label>
                    <input type="text" wire:model.defer="shared.cta_zoom" class="bo-input w-full">
                    @error('shared.cta_zoom') <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                </div>
                <datalist id="juecesSharedList">
                    @foreach($juecesOptions as $nombre)
                        <option value="{{ $nombre }}">
                    @endforeach
                </datalist>
                <datalist id="actaAnfitrionSharedList">
                    @foreach($actaAnfitrionOptions as $nombre)
                        <option value="{{ $nombre }}">
                    @endforeach
                </datalist>
            </div>
        </div>
        <hr>
        <!-- Acciones -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium bo-strong">Audiencias a registrar</h3>
            <button type="button" wire:click="addRow" class="bo-btn bo-btn--secondary bo-panel-subtle bo-strong px-4 py-2 rounded-md ">
                + Agregar fila
            </button>
        </div>

        <!-- Filas -->
        @forelse($items as $i => $row)
            <div class="border rounded-lg bo-panel-subtle p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold">Fila #{{ $i + 1 }}</span>
                    <button type="button" wire:click="removeRow({{ $i }})" class="bo-btn bo-btn--secondary bo-text--danger transition-transform duration-150 ease-out
 hover:-translate-y-0.5 active:translate-y-0
 focus:outline-none focus-visible:ring-2 transform-gpu"><i class="fas fa-trash"></i></button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="bo-label block text-sm font-medium bo-strong mb-1">RIT*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.rit" wire:input.debounce.600ms="buscarPorRit({{ $i }})"  required class="bo-input w-full" placeholder="Ej: 123-2025">
                        @error("items.$i.rit") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="bo-label block text-sm font-medium bo-strong mb-1">RUC*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.ruc" required class="bo-input w-full">
                        @error("items.$i.ruc") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado de Causa*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.encargado_causa" required list="encargadoCausaBatchList" class="bo-input w-full">
                        @error("items.$i.encargado_causa") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="bo-label block text-sm font-medium bo-strong mb-1">Acta*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.acta" required list="actaBatchList" class="bo-input w-full">
                        @error("items.$i.acta") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                    </div>
                    <datalist id="encargadoCausaBatchList">
                        @foreach($encargadoCausaOptions as $nombre)
                            <option value="{{ $nombre }}">
                        @endforeach
                    </datalist>
                    <datalist id="actaBatchList">
                        @foreach($actaAnfitrionOptions as $nombre)
                            <option value="{{ $nombre }}">
                        @endforeach
                    </datalist>
                    <div>
                        <label class="bo-label block text-sm font-medium bo-strong mb-1">Observación</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.obs" class="bo-input w-full">
                    </div>
                </div>
                <!-- Acusados -->
                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold bo-strong mb-2">Acusados (mínimo 1 por fila)</h4>
                    @error("items.$i.acusados") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bo-panel-subtle p-3 rounded border">
                        <input type="text" placeholder="Nombre completo" class="bo-input w-full"
                               wire:model.defer="items.{{ $i }}.nuevoAcusado.nombre_completo">
                        <input type="text" placeholder="Situación" class="bo-input w-full" list="situaciones"
                               wire:model.defer="items.{{ $i }}.nuevoAcusado.situacion">
                        <input type="text" placeholder="Medida cautelar" class="bo-input w-full"
                               wire:model.defer="items.{{ $i }}.nuevoAcusado.medida_cautelar">
                        <div class="flex gap-2">
                            <input type="text" placeholder="Forma notificación" class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.nuevoAcusado.forma_notificacion">
                            <button type="button" wire:click="addAcusado({{ $i }})"
                                    class="bo-btn bo-btn--secondary shrink-0 bo-panel-subtle bo-strong px-3 rounded-md ">
                                + Agregar
                            </button>
                        </div>
                    </div>

                    @error("items.$i.nuevoAcusado.nombre_completo") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                    @error("items.$i.nuevoAcusado.situacion") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror

                    @if(count($items[$i]['acusados']) > 0)
                        <div class="overflow-x-auto mb-3 border rounded">
                            <table class="bo-table bo-audiencias-table text-sm">
                                <thead class="bo-panel-subtle">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium bo-muted">Nombre</th>
                                        <th class="px-3 py-2 text-left font-medium bo-muted">Situación</th>
                                        <th class="px-3 py-2 text-left font-medium bo-muted">Medida</th>
                                        <th class="px-3 py-2 text-left font-medium bo-muted">Notificación</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bo-panel divide-y bo-border">
                                    @foreach($items[$i]['acusados'] as $k => $acus)
                                        <tr>
                                            <td class="px-3 py-2">{{ $acus['nombre_completo'] }}</td>
                                            <td class="px-3 py-2">{{ $acus['situacion'] }}</td>
                                            <td class="px-3 py-2">{{ $acus['medida_cautelar'] }}</td>
                                            <td class="px-3 py-2">{{ $acus['forma_notificacion'] }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <button type="button" wire:click="removeAcusado({{ $i }}, {{ $k }})" class="bo-btn bo-btn--secondary bo-text--danger hover:underline"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm bo-muted italic">No hay filas. Agrega al menos una.</p>
        @endforelse

        <datalist id="situaciones">
            @foreach($situaciones as $s)
                <option value="{{ $s }}">
            @endforeach
        </datalist>

        <div class="flex justify-end pt-2">
            <button type="submit" class="bo-btn bo-btn--primary px-6 py-2 rounded-md">
                Guardar Audiencias Corta
            </button>
        </div>
    </form>

    @script
    <script>
    // Scroll al primer error de backend (cuando falla $this->validate())
    document.addEventListener('scroll-first-error', () => {
        const firstErr = document.querySelector('[data-error]');
        const target = firstErr?.closest('div')?.querySelector('input,select,textarea') || firstErr;
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus?.({ preventScroll: true });
        }
    });

    document.addEventListener('alerta-success', () => {
        SwalToast.fire({ icon: 'success', title: 'Audiencias cortas registradas correctamente.' });
    });
    </script>
    @endscript
</div>

</div>
