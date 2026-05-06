<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bitacora Pruebas</h2>
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                @if (session('ok'))
                    <div class="mb-4 rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                @endif

                <h3 class="text-lg font-semibold mb-4">Nuevo dia de bitacora</h3>

                <div class="mb-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="toggleViewer"
                        class="inline-flex items-center px-4 py-2 rounded-md border border-sky-200 text-sky-700 bg-sky-50 hover:bg-sky-100"
                    >
                        {{ $showViewer ? 'Ocultar modo visualizacion' : 'Ver modo visualizacion' }}
                    </button>

                    <button
                        type="button"
                        wire:click="toggleNotificationForm"
                        class="inline-flex items-center px-4 py-2 rounded-md border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100"
                    >
                        Enviar notificacion de bitacora
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">RIT</label>
                        <input
                            type="text"
                            list="rit-options"
                            wire:model.live="rit"
                            class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                            placeholder="Ej: 123-2026"
                        >
                        <datalist id="rit-options">
                            @foreach ($ritsSugeridosUsuario as $ritOption)
                                <option value="{{ $ritOption }}"></option>
                            @endforeach
                        </datalist>
                        @error('rit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-700 mb-1">Enlace documentos</label>
                        <input
                            type="url"
                            wire:model.live="enlaceActa"
                            class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                            placeholder="https://..."
                        >
                        @error('enlaceActa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Numero dia</label>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="numeroDia"
                            class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        >
                        @error('numeroDia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Fecha dia</label>
                        <input
                            type="date"
                            wire:model.live="fechaDia"
                            class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        >
                        @error('fechaDia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:self-end">
                        <button
                            type="button"
                            wire:click="createDia"
                            class="w-full px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-sky-700"
                        >
                            Agregar dia
                        </button>
                    </div>
                </div>

                @if ($showNotificationForm)
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold mb-4">Enviar notificacion de bitacora</h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-3">
                            <label class="block text-sm text-gray-700 mb-1">Correo destinatario 1</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioUno"
                                class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="correo1@dominio.cl"
                            >
                            @error('destinatarioUno') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm text-gray-700 mb-1">Correo destinatario 2</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioDos"
                                class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="correo2@dominio.cl"
                            >
                            @error('destinatarioDos') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm text-gray-700 mb-1">Correo destinatario 3</label>
                            <input
                                type="email"
                                wire:model.live="destinatarioTres"
                                class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="correo3@dominio.cl"
                            >
                            @error('destinatarioTres') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <button
                                type="button"
                                wire:click="sendBitacoraNotification"
                                class="w-full px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                Enviar notificacion
                            </button>
                        </div>

                        <div class="md:col-span-12">
                            @error('enlaceActa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-semibold mb-4">Bitacora por RIT</h3>

                @if ($rit === '')
                    <p class="text-sm text-gray-600">Selecciona o escribe un RIT para ver y cargar informacion.</p>
                @elseif ($dias->isEmpty())
                    <p class="text-sm text-gray-600">No hay dias registrados para este RIT.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($dias as $dia)
                            <div class="rounded-lg border border-gray-200">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 px-4 py-3 bg-gray-50 border-b">
                                    <div>
                                        <p class="font-semibold">Dia {{ $dia->numero_dia }} - {{ $dia->fecha_dia?->format('d-m-Y') }}</p>
                                        <p class="text-xs text-gray-500">RIT: {{ $dia->rit }}</p>
                                        @if (!empty($dia->enlace_acta))
                                            <a href="{{ $dia->enlace_acta }}" target="_blank" rel="noopener noreferrer" class="text-xs text-sky-700 hover:underline">
                                                Ver Pruebas del Juicio
                                            </a>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="deleteDia({{ $dia->id }})"
                                        wire:confirm="Se eliminara el dia y sus pruebas. Continuar?"
                                        class="inline-flex items-center px-3 py-1.5 rounded-md border border-red-200 text-red-700 hover:bg-red-50"
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
                                            class="md:col-span-2 px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                        >
                                        @error("newItems.{$dia->id}.nombre_prueba") <p class="text-xs text-red-600 mt-1 md:col-span-2">{{ $message }}</p> @enderror

                                        <div>
                                            <input
                                                type="text"
                                                wire:model.live="newItems.{{ $dia->id }}.institucion_exhibe"
                                                list="instituciones-sugeridas"
                                                placeholder="Institucion que exhibe"
                                                class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                            >
                                            @error("newItems.{$dia->id}.institucion_exhibe") <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="addItem({{ $dia->id }})"
                                            @disabled(! $this->canAddItem($dia->id))
                                            class="px-3 py-2 rounded-md bg-sky-600 text-white hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-sky-600"
                                        >
                                            Agregar prueba
                                        </button>
                                    </div>

                                    <datalist id="instituciones-sugeridas">
                                        <option value="Min. Publico"></option>
                                        <option value="Def. DPP"></option>
                                    </datalist>

                                    @if ($dia->items->isEmpty())
                                        <p class="text-sm text-gray-500">Sin pruebas registradas para este dia.</p>
                                    @else
                                        <div class="overflow-x-auto border rounded-md">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-50 text-gray-700">
                                                    <tr>
                                                        <th class="text-left px-3 py-2">Prueba</th>
                                                        <th class="text-left px-3 py-2">Institucion exhibe</th>
                                                        <th class="text-left px-3 py-2">Ingresado por</th>
                                                        <th class="text-right px-3 py-2">Accion</th>
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
                                                                    class="text-red-700 hover:text-red-900"
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 md:p-4">
                    <livewire:bitacora-pruebas-viewer :show-hero="false" :wire:key="'bp-viewer-backoffice'" />
                </div>
            </div>
        @endif
    </div>
</div>
