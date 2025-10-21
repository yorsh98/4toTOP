<div>
    @php($isFull = $modo === 'full')

    {{-- Barra de búsqueda y controles --}}
    @php($isFull = $modo === 'full')

    <div class="d-flex justify-content-between mb-3">
        <div class="input-group w-50">
            <input type="text"
                class="form-control rounded"
                placeholder="Buscar (N°, Año, Causa, Solicitante, Motivo)..."
                wire:model.live.debounce.200ms="search">
            <span class="input-group-text">
                <div wire:loading wire:target="search">
                    <span class="spinner-border spinner-border-sm"></span>
                </div>
            </span>
        </div>

        <div class="d-flex gap-2">
            <select wire:model.live.debounce.100ms="filtroAnio" class="form-control">
                <option value="">— Todos los años —</option>
                @foreach($anios as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>

            <select class="form-select w-auto rounded" wire:model.live="perPage">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>

            {{-- AHORA SIEMPRE visible (full y vista) --}}
            <button class="btn btn-outline-secondary" wire:click="toggleEliminados">
                {{ $verEliminados ? 'Ver Activos' : 'Eliminados' }}
            </button>

            {{-- SOLO en modo full --}}
            @if($isFull)
                <button class="btn btn-primary" wire:click="$toggle('showCreateForm')">
                    {{ $showCreateForm ? 'Cancelar' : 'Nueva Libertad' }}
                </button>
            @endif
        </div>
    </div>


    {{-- Formulario de creación (solo modo full) --}}
    <div x-data="{ showForm: @entangle('showCreateForm') }"
         x-show="showForm && @js($isFull)"
         style="display: none;"
         x-transition.scale.origin.center
         class="card mb-3">
        <div class="card-header bg-primary text-white rounded">
            <h5 class="mb-0">Crear Libertad</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="create">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Causa Asig (máx 10)</label>
                        <input type="text" class="form-control rounded" wire:model="newCausaAsig" required @if(!$isFull) disabled @endif>
                        @error('newCausaAsig') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Solicitante</label>
                        <input type="text" class="form-control rounded" wire:model="newUserSolicitante" required @if(!$isFull) disabled @endif>
                        @error('newUserSolicitante') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Motivo / Dirigido</label>
                        <input type="text" class="form-control rounded" wire:model="newUserDirigido" required @if(!$isFull) disabled @endif>
                        @error('newUserDirigido') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Año (opcional)</label>
                        <input type="text" class="form-control rounded" wire:model="newAnio" placeholder="{{ now()->year }}" @if(!$isFull) disabled @endif>
                        @error('newAnio') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success" @if(!$isFull) disabled @endif>Guardar</button>
                        <button type="button" class="btn btn-secondary"
                                wire:click="resetCreateForm"
                                @click="showForm = false">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-responsive rounded border">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th wire:click="sortBy('Numentregado')">N° ENTREGADO</th>
                    <th wire:click="sortBy('año')">AÑO</th>
                    <th wire:click="sortBy('CausaAsig')">CAUSA ASIG</th>
                    <th wire:click="sortBy('UserSolicitante')">SOLICITANTE</th>
                    <th wire:click="sortBy('UserDirigido')">MOTIVO / DIRIGIDO</th>
                    <th wire:click="sortBy('created_at')">CREADO</th>
                    @if($isFull)
                        <th>ACCIONES</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($libertades as $l)
                    <tr>
                        <td>{{ $l->Numentregado }}</td>
                        <td>{{ $l->año }}</td>
                        <td>{{ $l->CausaAsig }}</td>
                        <td>{{ $l->UserSolicitante }}</td>
                        <td>{{ $l->UserDirigido }}</td>
                        <td>{{ optional($l->created_at)->format('Y-m-d H:i') }}</td>

                        @if($isFull)
                            <td class="d-flex gap-2">
                                @if(!$l->deleted_at)
                                    <button wire:click="edit({{ $l->id }})" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $l->id }})" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @else
                                    <button wire:click="restore({{ $l->id }})" class="btn btn-sm btn-success">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isFull ? 7 : 6 }}" class="text-center py-4">
                            No se encontraron registros
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-end align-items-center mt-3">
        {{ $libertades->links() }}
    </div>

    {{-- Modal de Edición (solo full) --}}
<div
    x-data="{ open: false }"
    x-on:open-edit-modal.window="open = true"
    x-on:close-edit-modal.window="open = false"
    x-cloak
    class="relative z-50">

    {{-- Backdrop --}}
    <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50" x-transition.opacity></div>

    {{-- Dialog --}}
    <div x-show="open" class="fixed inset-0 flex items-center justify-center p-4" x-transition>
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Editar Libertad</h2>

            <form wire:submit.prevent="update">
                <div class="mb-3">
                    <label class="form-label">N° Entregado</label>
                    <input type="number" wire:model.defer="editNumEntregado" class="form-control rounded" required>
                    @error('editNumEntregado') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Año</label>
                    <input type="text" wire:model.defer="editAnio" class="form-control rounded" required>
                    @error('editAnio') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Causa Asig</label>
                    <input type="text" wire:model.defer="editCausaAsig" class="form-control rounded" required>
                    @error('editCausaAsig') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Solicitante</label>
                    <input type="text" wire:model.defer="editUserSolicitante" class="form-control rounded" required>
                    @error('editUserSolicitante') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Motivo / Dirigido</label>
                    <input type="text" wire:model.defer="editUserDirigido" class="form-control rounded" required>
                    @error('editUserDirigido') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
