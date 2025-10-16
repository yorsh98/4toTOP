<div
    x-data="{
        // vincula la prop pública de Livewire
        editingId: @entangle('editingId'),

        init() {
            // ejemplo: observar cambios (opcional)
            this.$watch('editingId', v => {
                // console.log('editingId ->', v)
            });

            window.addEventListener('toast', e => {
                const msg = e.detail?.message ?? 'OK';
                const t = document.createElement('div');
                t.textContent = msg;
                t.className = 'fixed top-4 right-4 bg-black/80 text-white text-sm px-4 py-2 rounded-xl shadow';
                document.body.appendChild(t);
                setTimeout(()=> t.remove(), 2200);
            });

            window.addEventListener('scrollToForm', () => {
                document.getElementById('ausentismo-form')?.scrollIntoView({ behavior:'smooth', block:'start' });
            });
        }
    }"
    class="space-y-6"
>
    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Ausentismo del Tribunal</h1>
            <p class="text-gray-500 text-sm">Registra y gestiona ausencias por rango de fechas, con motivo/observación.</p>
        </div>
       
    </div>

    {{-- Formulario --}}
    <div id="ausentismo-form" class="bg-white rounded-2xl shadow p-4 md:p-6">
        <h2 class="text-base font-medium text-gray-800 mb-3">
            {{ $this->editingId ? 'Editar ausentismo' : 'Nuevo ausentismo' }}
        </h2>

        <form wire:submit.prevent="{{ $this->editingId ? 'update' : 'create' }}" 
        class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4">

        {{-- Nombre (4) --}}
        <div class="md:col-span-4">
            <label class="block text-sm text-gray-600 mb-1">Nombre del funcionario</label>
            <input type="text" wire:model.defer="funcionario_nombre" required minlength="3" maxlength="150"
                class="w-full h-10 px-3 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('funcionario_nombre') ring-2 ring-red-500 @enderror">
            @error('funcionario_nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Tipo de permiso (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Tipo de permiso</label>
            <select wire:model.defer="tipo_permiso" required
                    class="w-full h-10 px-3 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('tipo_permiso') ring-2 ring-red-500 @enderror">
                <option value="">— Selecciona —</option>
                @foreach($tipos_ausencia as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
            @error('tipo_permiso') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Cargo (ahora 2, antes 3) --}}
        <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Cargo</label>
            <select wire:model.defer="cargo" required
                    class="w-full h-10 px-3 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('cargo') ring-2 ring-red-500 @enderror">
                <option value="">— Selecciona —</option>
                @foreach($cargos as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            @error('cargo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Fecha inicio (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Fecha inicio</label>
            <input type="date" wire:model.defer="fecha_inicio" required
                class="w-full h-10 px-3 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fecha_inicio') ring-2 ring-red-500 @enderror">
            @error('fecha_inicio') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Fecha término (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Fecha término</label>
            <input type="date" wire:model.defer="fecha_termino" required
                class="w-full h-10 px-3 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fecha_termino') ring-2 ring-red-500 @enderror">
            @error('fecha_termino') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Observación (12, segunda fila completa) --}}
        <div class="md:col-span-12">
            <label class="block text-sm text-gray-600 mb-1">Observación (tipo de permiso / motivo)</label>
            <textarea wire:model.defer="observacion" rows="3" placeholder="Licencia médica, feriado legal, permiso administrativo, comisión de servicio, etc."
                    class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('observacion') ring-2 ring-red-500 @enderror"></textarea>
            @error('observacion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Botones --}}
        <div class="md:col-span-12 flex items-center gap-2 pt-2">
            @if($this->editingId)
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl shadow hover:bg-blue-700">
                    Actualizar
                </button>
                <button type="button" wire:click="resetForm"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-xl hover:bg-gray-300">
                    Cancelar
                </button>
            @else
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700">
                    Guardar
                </button>
            @endif
        </div>
    </form>

    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-2">Funcionario</th>
                        <th class="text-left px-4 py-2">Cargo</th>
                        <th class="text-left px-4 py-2">Tipo de permiso</th>
                        <th class="text-left px-4 py-2">Inicio</th>
                        <th class="text-left px-4 py-2">Término</th>
                        <th class="text-left px-4 py-2">Observación</th>
                        <th class="text-right px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($items as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $row->funcionario_nombre }}</td>
                            <td class="px-4 py-2">{{ $row->cargo }}</td>
                            <td class="px-4 py-2">{{ $row->tipo_permiso }}</td>
                            <td class="px-4 py-2">{{ $row->fecha_inicio?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $row->fecha_termino?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">
                                <span class="line-clamp-2">{{ $row->observacion }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $row->id }})"
                                            class="px-3 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="delete({{ $row->id }})"
                                            class="px-3 py-1 rounded-lg bg-red-50 text-red-700 hover:bg-red-100"
                                            onclick="return confirm('¿Eliminar este registro?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                No hay registros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $items->onEachSide(1)->links() }}
        </div>
    </div>
</div>
