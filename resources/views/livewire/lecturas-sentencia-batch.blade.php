<div>
    <div class="max-w-6xl mx-auto p-6 bg-white">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Lecturas de Sentencia</h2>

        @php
            $baseInput = "w-full px-3 py-2 border rounded-md border-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          invalid:border-red-500 invalid:focus:border-red-500 invalid:focus:ring-red-300
                          scroll-mt-24";
        @endphp

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
                <h3 class="text-lg font-medium text-gray-800">Audiencias a registrar</h3>
                <button type="button" wire:click="addRow"
                        class="bg-blue-100 text-black px-4 py-2 rounded-md hover:bg-blue-200">
                    + Agregar audiencia
                </button>
            </div>
            <!-- Filas -->
            @forelse($items as $i => $row)
                <div class="border rounded-lg bg-purple-50 p-4 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold">Audiencia #{{ $i + 1 }}</span>
                        <button type="button" wire:click="removeRow({{ $i }})"
                                class="text-red-600 text-sm hover:underline">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RIT*</label>
                            <input type="text" required class="{{ $baseInput }}" placeholder="Ej: 123-2025"
                                   wire:model.defer="items.{{ $i }}.rit"
                                   wire:input.debounce.600ms="buscarPorRit({{ $i }})">
                            @error("items.$i.rit") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha*</label>
                            <input type="date" required class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.fecha">
                            @error("items.$i.fecha") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio*</label>
                            <input type="time" required class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.hora_inicio">
                            @error("items.$i.hora_inicio") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo audiencia*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   placeholder="Lectura de Sentencia"
                                   wire:model.defer="items.{{ $i }}.tipo_audiencia">
                            @error("items.$i.tipo_audiencia") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Juez Redactor*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   placeholder="Ej: Juez(a) Apellido"
                                   wire:model.defer="items.{{ $i }}.JuezR">
                            @error("items.$i.JuezR") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>                    
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sala*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.sala">
                            @error("items.$i.sala") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Zoom</label>
                            <input type="text" class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.cta_zoom">
                            @error("items.$i.cta_zoom") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RUC*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.ruc">
                            @error("items.$i.ruc") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encargado de sala*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   placeholder="Nombre completo"
                                   wire:model.defer="items.{{ $i }}.encargado_sala">
                            @error("items.$i.encargado_sala") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Acta*</label>
                            <input type="text" required class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.acta">
                            @error("items.$i.acta") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Acusados -->
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Acusados (mínimo 1 por fila)</h4>
                        @error("items.$i.acusados") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-gray-50 p-3 rounded border">
                            <input type="text" placeholder="Nombre completo" class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.nuevoAcusado.nombre_completo">
                            <input type="text" placeholder="Situación" class="{{ $baseInput }}" list="situaciones"
                                   wire:model.defer="items.{{ $i }}.nuevoAcusado.situacion">
                            <input type="text" placeholder="Medida cautelar" class="{{ $baseInput }}"
                                   wire:model.defer="items.{{ $i }}.nuevoAcusado.medida_cautelar">
                            <div class="flex gap-2">
                                <input type="text" placeholder="Forma notificación" class="{{ $baseInput }}"
                                       wire:model.defer="items.{{ $i }}.nuevoAcusado.forma_notificacion">
                                <button type="button" wire:click="addAcusado({{ $i }})"
                                        class="shrink-0 bg-blue-100 text-black px-3 rounded-md hover:bg-blue-200">
                                    + Agregar
                                </button>
                            </div>
                        </div>

                        @error("items.$i.nuevoAcusado.nombre_completo") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @error("items.$i.nuevoAcusado.situacion") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror

                        @if(count($items[$i]['acusados']) > 0)
                            <div class="overflow-x-auto mb-3 border rounded">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-gray-600">Nombre</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-600">Situación</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-600">Medida</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-600">Notificación</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($items[$i]['acusados'] as $k => $acus)
                                            <tr>
                                                <td class="px-3 py-2">{{ $acus['nombre_completo'] }}</td>
                                                <td class="px-3 py-2">{{ $acus['situacion'] }}</td>
                                                <td class="px-3 py-2">{{ $acus['medida_cautelar'] }}</td>
                                                <td class="px-3 py-2">{{ $acus['forma_notificacion'] }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <button type="button" wire:click="removeAcusado({{ $i }}, {{ $k }})"
                                                            class="text-red-600 hover:underline">
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
                <p class="text-sm text-gray-500 italic">No hay filas. Agrega al menos una.</p>
            @endforelse

            <datalist id="situaciones">
                @foreach($situaciones as $s)
                    <option value="{{ $s }}">
                @endforeach
            </datalist>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-green-300 text-black px-6 py-2 rounded-md hover:bg-green-500">
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

        document.addEventListener('alerta-success', () => {
            Swal?.fire?.({
                icon: 'success',
                title: '¡Listo!',
                text: 'Lecturas de Sentencia registradas correctamente.',
                confirmButtonColor: '#3085d6'
            });
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
