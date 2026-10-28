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
                SwalToast.fire({ icon: 'success', title: e.detail?.message ?? 'OK' });
            });

            window.addEventListener('scrollToForm', () => {
                document.getElementById('ausentismo-form')?.scrollIntoView({ behavior:'smooth', block:'start' });
            });
        },

        async confirmDelete(id) {
            const result = await confirmDestructiveAction('¿Eliminar registro?', 'Esta acción no se puede deshacer.');
            if (result.isConfirmed) this.$wire.delete(id);
        }
    }"
    class="space-y-6"
>
    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold bo-strong">Ausentismo del Tribunal</h1>
            <p class="bo-muted text-sm">Registra y gestiona ausencias por rango de fechas, con motivo/observación.</p>
        </div>
       
    </div>

    {{-- Formulario --}}
    <div id="ausentismo-form" class="bo-section p-4 md:p-6">
        <h2 class="text-base font-medium bo-strong mb-3">
            {{ $this->editingId ? 'Editar ausentismo' : 'Nuevo ausentismo' }}
        </h2>

        <form wire:submit.prevent="{{ $this->editingId ? 'update' : 'create' }}" 
        class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4">

        {{-- Nombre (4) --}}
        <div class="md:col-span-4">
            <label class="block text-sm bo-muted mb-1">Nombre del funcionario</label>
            <input type="text" wire:model.defer="funcionario_nombre" required minlength="3" maxlength="150" list="funcionariosCatalogo"
                class="bo-input w-full">
            <datalist id="funcionariosCatalogo">
                @foreach($funcionarios_sugeridos as $nombre)
                    <option value="{{ $nombre }}">
                @endforeach
            </datalist>
            @error('funcionario_nombre') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Tipo de permiso (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm bo-muted mb-1">Tipo de permiso</label>
            <select wire:model.defer="tipo_permiso" required
                    class="bo-select w-full">
                <option value="">— Selecciona —</option>
                @foreach($tipos_ausencia as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
            @error('tipo_permiso') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Cargo (ahora 2, antes 3) --}}
        <div class="md:col-span-2">
            <label class="block text-sm bo-muted mb-1">Cargo</label>
            <select wire:model.defer="cargo" required
                    class="bo-select w-full">
                <option value="">— Selecciona —</option>
                @foreach($cargos as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            @error('cargo') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Fecha inicio (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm bo-muted mb-1">Fecha inicio</label>
            <input type="date" wire:model.defer="fecha_inicio" required
                class="bo-input w-full">
            @error('fecha_inicio') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Fecha término (2) --}}
        <div class="md:col-span-2">
            <label class="block text-sm bo-muted mb-1">Fecha término</label>
            <input type="date" wire:model.defer="fecha_termino" required
                class="bo-input w-full">
            @error('fecha_termino') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Observación (12, segunda fila completa) --}}
        <div class="md:col-span-12">
            <label class="block text-sm bo-muted mb-1">Observación (tipo de permiso / motivo)</label>
            <textarea wire:model.defer="observacion" rows="3" placeholder="Licencia médica, feriado legal, permiso administrativo, comisión de servicio, etc."
                    class="bo-input w-full"></textarea>
            @error('observacion') <p class="bo-text--danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Botones --}}
        <div class="md:col-span-12 flex items-center gap-2 pt-2">
            @if($this->editingId)
                <button type="submit"
                        class="bo-btn bo-btn--primary">
                    Actualizar
                </button>
                <button type="button" wire:click="resetForm"
                        class="bo-btn bo-btn--secondary">
                    Cancelar
                </button>
            @else
                <button type="submit"
                        class="bo-btn bo-btn--primary">
                    Guardar
                </button>
            @endif
        </div>
    </form>

    </div>

    {{-- Tabla --}}
    <div class="bo-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bo-table min-w-full text-sm">
                <thead class="">
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
                <tbody class="">
                    @forelse($items as $row)
                        <tr class="">
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
                                    <button aria-label="Editar registro" wire:click="edit({{ $row->id }})"
                                            class="bo-btn-icon">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" x-on:click="confirmDelete({{ $row->id }})"
                                            class="bo-btn-icon bo-text--danger"
                                            aria-label="Eliminar registro">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center bo-muted">
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
