<div>
    <div class="max-w-6xl mx-auto p-6 bo-panel">
        <h2 class="text-2xl font-bold bo-strong mb-6 bo-cat-heading bo-cat-lectura">Lecturas de Sentencia</h2>

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
            class="space-y-8">
            <!-- Acciones -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium bo-strong">Audiencias a registrar</h3>
                @can('audiencias.crear')
                <button type="button" wire:click="addRow"
                        class="bo-btn bo-btn--secondary bo-panel-subtle bo-strong px-4 py-2 rounded-md ">
                    + Agregar audiencia
                </button>
                @endcan
            </div>
            <!-- Filas -->
            @forelse($items as $i => $row)
                <div class="border rounded-lg bo-panel-subtle p-4 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold">Audiencia #{{ $i + 1 }}</span>
                        @unless ($editId !== null && $i === 0)
                        <button type="button" wire:click="removeRow({{ $i }})"
                                class="bo-btn bo-btn--secondary bo-text--danger text-sm transition-transform duration-150 ease-out
 hover:-translate-y-0.5 active:translate-y-0
 focus:outline-none focus-visible:ring-2 transform-gpu">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endunless
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">RIT*</label>
                            <input type="text" required class="bo-input w-full" placeholder="Ej: 123-2025"
                                   wire:model.defer="items.{{ $i }}.rit"
                                   wire:input.debounce.600ms="buscarPorRit({{ $i }})">
                            @error("items.$i.rit") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Fecha*</label>
                            <input type="date" required class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.fecha">
                            @error("items.$i.fecha") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Hora inicio*</label>
                            <input type="time" required class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.hora_inicio">
                            @error("items.$i.hora_inicio") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Observación</label>
                            <input type="text" class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.obs">
                        </div>
                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Redactor*</label>
                            <input type="text" required class="bo-input w-full" list="juecesLecturaList" wire:model.defer="items.{{ $i }}.JuezR">
                            @error("items.$i.JuezR") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Sala*</label>
                            <input type="text" required class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.sala">
                            @error("items.$i.sala") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Cuenta Zoom</label>
                            <input type="text" class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.cta_zoom">
                            @error("items.$i.cta_zoom") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>



                        <div>
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">RUC*</label>
                            <input type="text" required class="bo-input w-full"
                                   wire:model.defer="items.{{ $i }}.ruc">
                            @error("items.$i.ruc") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado de causa*</label>
                            <input type="text" required class="bo-input w-full" list="encargadoCausaLecturaList" wire:model.defer="items.{{ $i }}.encargado_sala">
                            @error("items.$i.encargado_sala") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="bo-label block text-sm font-medium bo-strong mb-1">Acta*</label>
                            <input type="text" required class="bo-input w-full" list="actaLecturaList" wire:model.defer="items.{{ $i }}.acta">
                            @error("items.$i.acta") <span data-error class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <datalist id="juecesLecturaList">
                            @foreach($juecesOptions as $nombre)
                                <option value="{{ $nombre }}">
                            @endforeach
                        </datalist>
                        <datalist id="encargadoCausaLecturaList">
                            @foreach($encargadoCausaOptions as $nombre)
                                <option value="{{ $nombre }}">
                            @endforeach
                        </datalist>
                        <datalist id="actaLecturaList">
                            @foreach($actaOptions as $nombre)
                                <option value="{{ $nombre }}">
                            @endforeach
                        </datalist>
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
                                                    <button type="button" wire:click="removeAcusado({{ $i }}, {{ $k }})"
                                                            class="bo-btn bo-btn--secondary bo-text--danger hover:underline">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                    Guardar Lecturas de Sentencia
                </button>
            </div>
        </form>

        @script
        <script>
        document.addEventListener('scroll-first-error', () => {
            const firstErr = document.querySelector('[data-error]');
            const target = firstErr?.closest('div')?.querySelector('input,select,textarea') || firstErr;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.focus?.({ preventScroll: true });
            }
        });

        document.addEventListener('alerta-success', (event) => {
        SwalToast.fire({ icon: 'success', title: `Lecturas de sentencia guardadas para el ${event.detail.fecha}.` });
        });

        document.addEventListener('max-filas', () => {
            Swal?.fire?.({
                icon: 'info',
                title: 'Límite alcanzado',
                text: 'Solo se permiten 5 filas por lote.',
                confirmButtonColor: '#3085d6'
            });
        });
        </script>
        @endscript
    </div>
</div>
