<x-slot:header>
    <x-page-heading eyebrow="Bitácora · Pruebas" title="Bitácora de pruebas" />
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
        <div class="bo-card overflow-hidden">
            <div class="p-6 bo-strong">
                <x-flash-notifications />

                <h3 class="text-lg font-semibold mb-4">Nuevo día de bitácora</h3>

                <div class="mb-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="toggleViewer"
                        class="inline-flex items-center px-4 py-2 rounded-md border bo-panel-subtle bo-text--info"
                    >
                        {{ $showViewer ? 'Ocultar modo visualización' : 'Ver modo visualización' }}
                    </button>

                    <button
                        type="button"
                        wire:click="toggleNotificationForm"
                        class="inline-flex items-center px-4 py-2 rounded-md border bo-panel-subtle"
                    >
                        Enviar notificación de bitácora
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-sm mb-1 bo-strong">RIT</label>
                        <input
                            type="text"
                            list="rit-options"
                            wire:model.live="rit"
                            class="w-full bo-input"
                            placeholder="Ej: 123-2026"
                        >
                        <datalist id="rit-options">
                            @foreach ($ritsSugeridosUsuario as $ritOption)
                                <option value="{{ $ritOption }}"></option>
                            @endforeach
                        </datalist>
                        @error('rit') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm mb-1 bo-strong">Enlace documentos</label>
                        <input
                            type="url"
                            wire:model.live="enlaceActa"
                            class="w-full bo-input"
                            placeholder="https://..."
                        >
                        @error('enlaceActa') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1 bo-strong">Número de día</label>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="numeroDia"
                            class="w-full bo-input"
                        >
                        @error('numeroDia') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1 bo-strong">Fecha día</label>
                        <input
                            type="date"
                            wire:model.live="fechaDia"
                            class="w-full bo-input"
                        >
                        @error('fechaDia') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:self-end">
                        <button
                            type="button"
                            wire:click="createDia"
                            class="w-full bo-btn bo-btn--primary"
                        >
                            Agregar día
                        </button>
                    </div>
                </div>

                @if ($showNotificationForm)
                <div class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">Enviar notificación de bitácora</h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-3">
                            <label class="block text-sm mb-1 bo-strong">Correo destinatario 1</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioUno"
                                class="w-full bo-input"
                                placeholder="correo1@dominio.cl"
                            >
                            @error('destinatarioUno') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm mb-1 bo-strong">Correo destinatario 2</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioDos"
                                class="w-full bo-input"
                                placeholder="correo2@dominio.cl"
                            >
                            @error('destinatarioDos') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm mb-1 bo-strong">Correo destinatario 3</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioTres"
                                class="w-full bo-input"
                                placeholder="correo3@dominio.cl"
                            >
                            @error('destinatarioTres') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <button
                                type="button"
                                wire:click="sendBitacoraNotification"
                                class="w-full bo-btn bo-btn--primary"
                            >
                                Enviar notificacion
                            </button>
                        </div>

                        <div class="md:col-span-12">
                            @error('enlaceActa') <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                        </div>

                        
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="bo-card overflow-hidden">
            <div class="p-6 bo-strong">
                <h3 class="text-lg font-semibold mb-4">Bitácora por RIT</h3>

                @if ($rit === '')
                    <p class="text-sm bo-muted">Selecciona o escribe un RIT para ver y cargar información.</p>
                @elseif ($dias->isEmpty())
                    <p class="text-sm bo-muted">No hay dias registrados para este RIT.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($dias as $dia)
                            <div class="rounded-lg border">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 px-4 py-3 border-b bo-panel-subtle">
                                    <div>
                                        <p class="font-semibold">Dia {{ $dia->numero_dia }} - {{ $dia->fecha_dia?->format('d-m-Y') }}</p>
                                        <p class="text-xs bo-muted">RIT: {{ $dia->rit }}</p>
                                        @if (!empty($dia->enlace_acta))
                                            <a href="{{ $dia->enlace_acta }}" target="_blank" rel="noopener noreferrer" class="text-xs hover:underline bo-text--info">
                                                Ver Pruebas del Juicio
                                            </a>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="deleteDia({{ $dia->id }})"
                                        wire:confirm="Se eliminara el dia y sus pruebas. Continuar?"
                                        class="inline-flex items-center px-3 py-1.5 rounded-md border border-red-200 hover:bg-red-50 bo-text--danger"
                                    >
                                        Eliminar dia
                                    </button>
                                </div>

                                <div class="p-4 space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                        <input
                                            type="text"
                                            wire:model.live="newItems.{{ $dia->id }}.nombre_prueba"
                                            placeholder="Nombre de la prueba"
                                            class="md:col-span-2 bo-input"
                                        >
                                        @error("newItems.{$dia->id}.nombre_prueba") <p class="text-xs mt-1 md:col-span-2 bo-text--danger">{{ $message }}</p> @enderror

                                        <div>
                                            <input
                                                type="text"
                                                wire:model.live="newItems.{{ $dia->id }}.institucion_exhibe"
                                                list="instituciones-sugeridas"
                                                placeholder="Institucion que exhibe"
                                                class="w-full bo-input"
                                            >
                                            @error("newItems.{$dia->id}.institucion_exhibe") <p class="text-xs mt-1 bo-text--danger">{{ $message }}</p> @enderror
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="addItem({{ $dia->id }})"
                                            @disabled(! $this->canAddItem($dia->id))
                                            class="bo-btn bo-btn--primary"
                                        >
                                            Agregar prueba
                                        </button>
                                    </div>

                                    <datalist id="instituciones-sugeridas">
                                        <option value="Min. Publico"></option>
                                        <option value="Def. DPP"></option>
                                    </datalist>

                                    @if ($dia->items->isEmpty())
                                        <p class="text-sm bo-muted">Sin pruebas registradas para este día.</p>
                                    @else
                                        <div class="overflow-x-auto border rounded-md">
                                            <table class="bo-table min-w-full text-sm">
                                                <thead class="bo-panel-subtle bo-strong">
                                                    <tr>
                                                        <th class="text-left px-3 py-2">Prueba</th>
                                                        <th class="text-left px-3 py-2">Institucion exhibe</th>
                                                        <th class="text-left px-3 py-2">Ingresado por</th>
                                                        <th class="text-right px-3 py-2">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y">
                                                    @foreach ($dia->items as $item)
                                                        <tr>
                                                            <td class="px-3 py-2">{{ $item->nombre_prueba }}</td>
                                                            <td class="px-3 py-2">{{ $item->institucion_exhibe }}</td>
                                                            <td class="px-3 py-2">{{ $item->usuario?->name ?? 'Usuario no disponible' }}</td>
                                                            <td class="px-3 py-2 text-right">
                                                                <button
                                                                    type="button"
                                                                    wire:click="deleteItem({{ $item->id }})"
                                                                    wire:confirm="Se eliminara esta prueba. Continuar?"
                                                                    class="bo-text--danger"
                                                                >
                                                                    Eliminar
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
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if ($showViewer)
            <div class="bo-card overflow-hidden">
                <div class="p-2 md:p-4">
                    <livewire:bitacora-pruebas-viewer :show-hero="false" :wire:key="'bp-viewer-backoffice'" />
                </div>
            </div>
        @endif
    </div>
</div>
