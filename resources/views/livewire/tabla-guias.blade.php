<div>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
 
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
                                <option value="1">1. 4toTOPSTGO</option>
                                <option value="2">2. Gendarmeria</option>
                                <option value="3">3. Fiscalia</option>
                                <option value="4">4. CAPJ</option>
                                <option value="5">5. Zonal STGO</option>
                                <option value="6">6. Defensoria</option>
                                <option value="7">7. Min. Interior</option>
                                <option value="8">8. C.D. Estado</option>
                                <option value="9">9. Defensores Privados</option>
                                <option value="10">10. PDI/Carabineros</option>
                                <option value="11">11. SML</option>
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
    <dialog id="editModal" wire:ignore.self class="modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Editar Guía</h5>
                    <button type="button" class="btn-close btn-close-white" 
                            wire:click="$dispatch('closeModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="update">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" wire:model="editNombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RUT</label>
                                <input type="text" class="form-control" wire:model="editRut">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" wire:model="editEmail">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono 1</label>
                                <input type="text" class="form-control" wire:model="editTelefono1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono 2</label>
                                <input type="text" class="form-control" wire:model="editTelefono2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Institución</label>
                                <select class="form-select" wire:model="editInstitucion" required>
                                    <option value="" disabled selected>Seleccionar institución</option>
                                    @foreach($instituciones as $key => $institucion)
                                        <option value="{{ $key }}">{{ $institucion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" 
                            wire:click="$dispatch('closeModal')">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="update">Guardar</button>
                </div>
            </div>
        </div>
    </dialog>
</div>

@script
   <script>
         document.addEventListener('alerta-exito', () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '¡Guia agregada correctamente!',
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

