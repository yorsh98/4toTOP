<div>
    <div class="max-w-6xl mx-auto p-6 bg-white">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Audiencias Cortas</h2>

    <div class="rounded border text-sm mb-4 text-center bg-amber-100 px-2 py-2">
        <span>Los datos <b>COMPARTIDOS</b> se aplican a todas las audiencias a registrar</span>
    </div>

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
        class="space-y-8"
    >
        <!-- Datos compartidos -->
        <div class="bg-lime-100 p-4 border rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Datos compartidos</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha*</label>
                    <input type="date" wire:model.defer="shared.fecha" required class="{{ $baseInput }}">
                    @error('shared.fecha') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio*</label>
                    <input type="time" wire:model.defer="shared.hora_inicio" required class="{{ $baseInput }}">
                    @error("shared.hora_inicio") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div> 
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Juez Presidente*</label>
                    <input type="text" wire:model.defer="shared.JuezP" required class="{{ $baseInput }}">
                    @error('shared.JuezP') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Juez Redactor*</label>
                    <input type="text" wire:model.defer="shared.JuezR" required class="{{ $baseInput }}">
                    @error('shared.JuezR') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Juez Integrante*</label>
                    <input type="text" wire:model.defer="shared.JuezI" required class="{{ $baseInput }}">
                    @error('shared.JuezI') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sala*</label>
                    <input type="text" wire:model.defer="shared.sala" required class="{{ $baseInput }}">
                    @error('shared.sala') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación*</label>
                    <input type="text" wire:model.defer="shared.ubicacion" required class="{{ $baseInput }}">
                    @error("shared.ubicacion") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div> 
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anfitrión</label>
                    <input type="text" wire:model.defer="shared.anfitrion" class="{{ $baseInput }}">
                    @error('shared.anfitrion') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Zoom</label>
                    <input type="text" wire:model.defer="shared.cta_zoom" class="{{ $baseInput }}">
                    @error('shared.cta_zoom') <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>                                               
            </div>
        </div>
        <hr>
        <!-- Acciones -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-800">Audiencias a registrar</h3>
            <button type="button" wire:click="addRow" class="bg-blue-100 text-black px-4 py-2 rounded-md hover:bg-blue-200">
                + Agregar fila
            </button>
        </div>

        <!-- Filas -->
        @forelse($items as $i => $row)
            <div class="border rounded-lg bg-lime-50 p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold">Fila #{{ $i + 1 }}</span>
                    <button type="button" wire:click="removeRow({{ $i }})" class="text-red-600 transition-transform duration-150 ease-out
                    hover:-translate-y-0.5 active:translate-y-0
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/50 transform-gpu"><i class="fas fa-trash"></i></button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RIT*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.rit" wire:input.debounce.600ms="buscarPorRit({{ $i }})"  required class="{{ $baseInput }}" placeholder="Ej: 123-2025">
                        @error("items.$i.rit") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RUC*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.ruc" required class="{{ $baseInput }}">
                        @error("items.$i.ruc") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>       
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Encargado de Causa*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.encargado_causa" required class="{{ $baseInput }}">
                        @error("items.$i.encargado_causa") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Acta*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.acta" required class="{{ $baseInput }}">
                        @error("items.$i.acta") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>   
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Audiencia*</label>
                        <input type="text" wire:model.defer="items.{{ $i }}.tipo_audiencia" required class="{{ $baseInput }}">
                        @error("items.$i.tipo_audiencia") <span data-error class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                                                <button type="button" wire:click="removeAcusado({{ $i }}, {{ $k }})" class="text-red-600 hover:underline"><i class="fas fa-trash"></i></button>
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
        Swal?.fire?.({
            icon: 'success',
            title: '¡Listo!',
            text: 'Audiencias Corta registradas correctamente.',
            confirmButtonColor: '#3085d6'
        });
    });
    </script>
    @endscript
</div>

</div>
