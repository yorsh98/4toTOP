<div>
    <!-- Barra de búsqueda y controles -->
    <div class="d-flex justify-content-between mb-3">
        <div class="input-group w-50">
            <input type="text" 
                   class="form-control rounded" 
                   placeholder="Buscar guías..." 
                   wire:model.live.debounce.100ms="search">
            <span class="input-group-text">
                <div wire:loading wire:target="search">
                    <span class="spinner-border spinner-border-sm"></span>
                </div>
            </span>
        </div>
       <div class="gap-2">
            <select wire:model.live.debounce.50ms="filtroInstitucion" class="form-control">
                <option value="">-- Todas --</option>
                @foreach($instituciones as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
        </div> 
        <div class="d-flex gap-2">
            <select class="form-select w-auto rounded" wire:model.live="perPage">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
            
            @if($modo === 'full')
                <button class="btn btn-primary" wire:click="$toggle('showCreateForm')">
                    {{ $showCreateForm ? 'Cancelar' : 'Nueva Guía' }}
                </button>
            @endif
        </div>
    </div>

    <!-- Formulario de creación -->
    @if($showCreateForm && $modo === 'full')
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded">
                <h5 class="mb-0">Crear Nueva Guía</h5>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control rounded" wire:model="newNombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUT</label>
                            <input type="text" class="form-control rounded" wire:model="newRut">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control rounded" wire:model="newEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono 1</label>
                            <input type="text" class="form-control rounded" wire:model="newTelefono1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono 2</label>
                            <input type="text" class="form-control rounded" wire:model="newTelefono2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Institución</label>
                            <select class="form-select rounded" wire:model="newInstitucion" required>
                                <option value="" selected>Seleccionar institución</option>
                                <option value="1">4toTOPSTGO</option>
                                <option value="2">Gendarmeria</option>
                                <option value="3">Fiscalia</option>
                                <option value="4">CAPJ</option>
                                <option value="5">Zonal STGO</option>
                                <option value="6">Defensoria</option>
                                <option value="7">Min. Interior</option>
                                <option value="8">C.D. Estado</option>
                                <option value="9">Defensores Privados</option>
                                <option value="10">PDI/Carabineros</option>
                                <option value="11">SML</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Guardar</button>
                            <button type="button" class="btn btn-secondary" wire:click="resetCreateForm">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
    

    <!-- Tabla de guías -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th wire:click="sortBy('nombre_completo')">NOMBRE</th>
                    <th wire:click="sortBy('rut')">RUT</th>
                    <th wire:click="sortBy('email')">EMAIL</th>
                    <th wire:click="sortBy('telefono1')">TELEFONO 1</th>
                    <th wire:click="sortBy('telefono2')">TELEFONO 2</th>
                    <th wire:click="sortBy('institucion')">INSTITUCION</th>
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
                                <button wire:click="edit({{ $guia->id }})" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $guia->id }})" 
                                        class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $modo === 'full' ? 7 : 6 }}" class="text-center py-4">
                            No se encontraron guías telefónicas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>Mostrando {{ $guias->firstItem() }} a {{ $guias->lastItem() }} de {{ $guias->total() }} registros</div>
        {{ $guias->links() }}
    </div>

    <!-- Modal de Edición -->
     <div wire:ignore
    x-data="{ open: false }" 
    x-on:open-edit-modal.window="open = true" 
    x-on:close-edit-modal.window="open = false"
    class="relative z-50">
    <!-- Fondo oscuro -->
        <div 
            x-show="open" 
            class="fixed inset-0 bg-black bg-opacity-50"
            x-transition.opacity
        ></div>

        <!-- Contenido modal -->
        <div 
            x-show="open" 
            class="fixed inset-0 flex items-center justify-center p-4"
            x-transition
        >
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Editar Guía</h2>

                <!-- Formulario Livewire -->
                <form wire:submit.prevent="update">
                    <div class="mb-4">
                        <label class="block mb-1">Nombre Completo</label>
                        <input type="text" wire:model.defer="editNombre" class="border p-2 w-full rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">RUT</label>
                        <input type="text" wire:model.defer="editRut" class="border p-2 w-full rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Email</label>
                        <input type="email" wire:model.defer="editEmail" class="border p-2 w-full rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Telefono 1</label>
                        <input type="number" wire:model.defer="editTelefono1" class="border p-2 w-full rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1">Telefono 2</label>
                        <input type="number" wire:model.defer="editTelefono2" class="border p-2 w-full rounded">
                    </div>
                    <div class="mb-4">
                            <label class="block mb-1">Institución</label>
                            <select class="form-select rounded" wire:model.defer="editInstitucion" required>
                                <option value="" selected>Seleccionar institución</option>
                                <option value="1">4toTOPSTGO</option>
                                <option value="2">Gendarmeria</option>
                                <option value="3">Fiscalia</option>
                                <option value="4">CAPJ</option>
                                <option value="5">Zonal STGO</option>
                                <option value="6">Defensoria</option>
                                <option value="7">Min. Interior</option>
                                <option value="8">C.D. Estado</option>
                                <option value="9">Defensores Privados</option>
                                <option value="10">PDI/Carabineros</option>
                                <option value="11">SML</option>
                            </select>
                        </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" @click="open = false"
                            class="px-4 py-2 bg-gray-300 rounded">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
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
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '¡Guia guardada correctamente!',
                confirmButtonColor: '#3085d6'
            });
        });
        
        window.addEventListener('alerta-deleted', () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '¡Guia Eliminada correctamente!',
                confirmButtonColor: '#3085d6'
            });
        }); 
        
     </script>

@endscript

