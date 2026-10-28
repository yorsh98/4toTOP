<div>
    <!-- Barra de búsqueda y controles -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="flex-1 min-w-0">
            <input type="text" 
                   class="bo-input w-full" 
                   placeholder="Buscar guías..." 
                   wire:model.live.debounce.100ms="search">
            <span class="bo-muted">
                <div wire:loading wire:target="search">
                    <span class="bo-muted"></span>
                </div>
            </span>
        </div>
       <div class="gap-2">
            <select wire:model.live.debounce.50ms="filtroInstitucion" class="bo-select">
                <option value="">-- Todas --</option>
                @foreach($instituciones as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
        </div> 
        <div class="flex gap-2">
            <select class="bo-select" wire:model.live="perPage">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
            
            @if($modo === 'full')
                <button class="bo-btn bo-btn--primary" wire:click="$toggle('showCreateForm')">
                    {{ $showCreateForm ? 'Cancelar' : 'Nueva Guía' }}
                </button>
            @endif
        </div>
    </div>

<!-- Formulario de creación con animación -->
    <div 
        x-data="{ showForm: @entangle('showCreateForm') }" 
        x-show="showForm && @js($modo === 'full')"
        style="display: none;" 
        x-transition.scale.origin.center
        class="bo-card mb-4">
        <div class="bo-panel-heading p-4">
            <h5 class="mb-0">Crear Nueva Guía</h5>
        </div>
        <div class="p-4">
            <form wire:submit.prevent="create">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Nombre</label>
                        <input type="text" class="bo-input w-full" wire:model="newNombre" required>
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">RUT</label>
                        <input type="text" class="bo-input w-full" wire:model="newRut">
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Email</label>
                        <input type="email" class="bo-input w-full" wire:model="newEmail">
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Teléfono 1</label>
                        <input type="text" class="bo-input w-full" wire:model="newTelefono1">
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Teléfono 2</label>
                        <input type="text" class="bo-input w-full" wire:model="newTelefono2">
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Institución</label>
                        <select class="bo-select w-full" wire:model="newInstitucion" required>
                            <option value="" selected>Seleccionar institución</option>
                            @foreach($instituciones as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="bo-btn bo-btn--primary">Guardar</button>
                        <button type="button" class="bo-btn bo-btn--secondary" 
                                wire:click="resetCreateForm" 
                                @click="showForm = false">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Tabla de guías -->
    <div class="overflow-x-auto bo-card">
        <table class="bo-table min-w-max">
            <thead class="">
                <tr>
                    <th wire:click="sortBy('nombre_completo')">NOMBRE</th>
                    <th wire:click="sortBy('rut')">RUT</th>
                    <th wire:click="sortBy('email')">EMAIL</th>
                    <th wire:click="sortBy('telefono1')">TELÉFONO 1</th>
                    <th wire:click="sortBy('telefono2')">TELÉFONO 2</th>
                    <th wire:click="sortBy('institucion')">INSTITUCIÓN</th>
                    @if($modo === 'full')
                        <th>ACCIONES</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($guias as $guia)
                    <tr>
                        <td>{{ $guia->nombre_completo }}</td>
                        <td>{{ $guia->rut }}</td>
                        <td>{{ $guia->email }}</td>
                        <td>{{ $guia->telefono1 }}</td>
                        <td>{{ $guia->telefono2 }}</td>
                        <td>{{ $instituciones[$guia->institucion] ?? 'Desconocido' }}</td>
                        @if($modo === 'full')
                            <td>
                                <button wire:click="edit({{ $guia->id }})" class="bo-btn-icon" aria-label="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $guia->id }})" 
                                        class="bo-btn-icon" aria-label="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $modo === 'full' ? 7 : 6 }}" class="text-center py-4 bo-muted">
                            No se encontraron guías telefónicas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">       
        {{ $guias->links() }}
    </div>

    <!-- Modal de Edición -->
    <div wire:ignore
        x-data="{ open: false }" 
        x-on:open-edit-modal.window="open = true" 
        x-on:close-edit-modal.window="open = false"
        class="relative z-50"
    >
        <div x-show="open" class="fixed inset-0 bo-scrim" x-transition.opacity></div>

        <div x-show="open" class="fixed inset-0 flex items-center justify-center p-4" x-transition>
            <div class="bo-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <h2 class="text-lg font-semibold mb-4">Editar Guía</h2>

                <form wire:submit.prevent="update">
                    <div class="mb-4">
                        <label class="bo-label mb-1">Nombre Completo</label>
                        <input type="text" wire:model.defer="editNombre" class="bo-input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="bo-label mb-1">RUT</label>
                        <input type="text" wire:model.defer="editRut" class="bo-input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="bo-label mb-1">Email</label>
                        <input type="email" wire:model.defer="editEmail" class="bo-input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="bo-label mb-1">Teléfono 1</label>
                        <input type="number" wire:model.defer="editTelefono1" class="bo-input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="bo-label mb-1">Teléfono 2</label>
                        <input type="number" wire:model.defer="editTelefono2" class="bo-input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="bo-label mb-1">Institución</label>
                        <select class="bo-select w-full" wire:model.defer="editInstitucion" required>
                            <option value="" selected>Seleccionar institución</option>
                            @foreach($instituciones as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" @click="open = false" class="bo-btn bo-btn--secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="bo-btn bo-btn--primary">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    document.addEventListener('alerta-exito', () => {
        SwalToast.fire({ icon: 'success', title: 'Guía guardada correctamente.' });
    });

    window.addEventListener('alerta-deleted', () => {
        SwalToast.fire({ icon: 'success', title: 'Guía eliminada correctamente.' });
    });
</script>
@endscript
