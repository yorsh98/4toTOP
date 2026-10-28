<div>
    <x-flash-notifications />
    @php($isFull = $modo === 'full')

    {{-- Barra de búsqueda y controles --}}
    @php($isFull = $modo === 'full')

    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="flex-1 min-w-0">
            <input type="text"
                class="bo-input w-full"
                placeholder="Buscar (N°, Año, Causa, Solicitante, Motivo)..."
                wire:model.live.debounce.200ms="search">
            <span class="bo-muted">
                <div wire:loading wire:target="search">
                    <span class="bo-muted"></span>
                </div>
            </span>
        </div>

        <div class="flex gap-2">
            <select wire:model.live.debounce.100ms="filtroAnio" class="bo-select">
                <option value="">— Todos los años —</option>
                @foreach($anios as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>

            <select class="bo-select" wire:model.live="perPage">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>

            {{-- AHORA SIEMPRE visible (full y vista) --}}
            <button class="bo-btn bo-btn--secondary" wire:click="toggleEliminados">
                {{ $verEliminados ? 'Ver Activos' : 'Eliminados' }}
            </button>

            {{-- SOLO en modo full --}}
            @if($isFull)
                <button class="bo-btn bo-btn--primary" wire:click="$toggle('showCreateForm')">
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
         class="bo-card mb-4">
        <div class="bo-panel-heading p-4">
            <h5 class="mb-0">Crear Libertad</h5>
        </div>
        <div class="p-4">
            <form wire:submit.prevent="create">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Causa Asig (máx 10)</label>
                        <input type="text" class="bo-input w-full" wire:model="newCausaAsig" required @if(!$isFull) disabled @endif>
                        @error('newCausaAsig') <div class="bo-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Solicitante</label>
                        <select class="bo-select w-full" wire:model="newUserSolicitante" required @if(!$isFull) disabled @endif>
                            <option value="">Seleccione un solicitante</option>
                            @foreach($solicitantes as $solicitante)
                                <option value="{{ $solicitante }}">{{ $solicitante }}</option>
                            @endforeach
                        </select>
                        @error('newUserSolicitante') <div class="bo-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Motivo / Dirigido</label>
                        <input type="text" class="bo-input w-full" wire:model="newUserDirigido" required @if(!$isFull) disabled @endif>
                        @error('newUserDirigido') <div class="bo-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="min-w-0">
                        <label class="bo-label mb-1">Año (opcional)</label>
                        <input type="text" class="bo-input w-full" wire:model="newAnio" placeholder="{{ now()->year }}" @if(!$isFull) disabled @endif>
                        @error('newAnio') <div class="bo-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="bo-btn bo-btn--primary" @if(!$isFull) disabled @endif>Guardar</button>
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

    {{-- Tabla --}}
    <div class="overflow-x-auto bo-card">
        <table class="bo-table min-w-max">
            <thead class="">
                <tr>
                    <th wire:click="sortBy('Numentregado')">N° ENTREGADO</th>
                    <th wire:click="sortBy('año')">AÑO</th>
                    <th wire:click="sortBy('CausaAsig')">CAUSA ASIG</th>
                    <th wire:click="sortBy('UserSolicitante')">SOLICITANTE</th>
                    <th wire:click="sortBy('UserDirigido')">MOTIVO / DIRIGIDO</th>
                    <th wire:click="sortBy('updated_at')">FECHA UTILIZACIÓN</th>
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
                        <td>{{ ($l->updated_at ?? $l->created_at)?->format('Y-m-d H:i') }}</td>

                        @if($isFull)
                            <td class="flex gap-2">
                                @if(!$l->deleted_at)
                                    <button wire:click="edit({{ $l->id }})" class="bo-btn-icon" aria-label="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                        x-on:click="confirmDestructiveAction('¿Eliminar libertad?', 'Esta acción no se puede deshacer.').then(result => { if (result.isConfirmed) $wire.confirmDelete({{ $l->id }}) })"
                                        class="bo-btn-icon" aria-label="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @else
                                    <button wire:click="restore({{ $l->id }})" class="bo-btn-icon" aria-label="Restaurar">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isFull ? 7 : 6 }}" class="text-center py-4 bo-muted">
                            No se encontraron registros
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-4">
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
    <div x-show="open" class="fixed inset-0 bo-scrim" x-transition.opacity></div>

    {{-- Dialog --}}
    <div x-show="open" class="fixed inset-0 flex items-center justify-center p-4" x-transition>
        <div class="bo-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
            <h2 class="text-lg font-semibold mb-4">Editar Libertad</h2>

            <form wire:submit.prevent="update">
                <div class="mb-3">
                    <label class="bo-label mb-1">N° Entregado</label>
                    <input type="number" wire:model.defer="editNumEntregado" class="bo-input w-full" required>
                    @error('editNumEntregado') <div class="bo-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="bo-label mb-1">Año</label>
                    <input type="text" wire:model.defer="editAnio" class="bo-input w-full" required>
                    @error('editAnio') <div class="bo-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="bo-label mb-1">Causa Asig</label>
                    <input type="text" wire:model.defer="editCausaAsig" class="bo-input w-full" required>
                    @error('editCausaAsig') <div class="bo-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="bo-label mb-1">Solicitante</label>
                    <input type="text" wire:model.defer="editUserSolicitante" class="bo-input w-full" required>
                    @error('editUserSolicitante') <div class="bo-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="bo-label mb-1">Motivo / Dirigido</label>
                    <input type="text" wire:model.defer="editUserDirigido" class="bo-input w-full" required>
                    @error('editUserDirigido') <div class="bo-error">{{ $message }}</div> @enderror
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="bo-btn bo-btn--secondary">Cancelar</button>
                    <button type="submit" class="bo-btn bo-btn--primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
