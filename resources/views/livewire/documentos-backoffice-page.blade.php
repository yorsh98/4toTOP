<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Backoffice Documentos</h2>
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('ok'))
            <div class="rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">
                Revisa los campos del formulario e intenta nuevamente.
            </div>
        @endif

        <div class="rounded-md bg-slate-100 px-4 py-2 text-sm text-slate-700">
            Ultima actualizacion del catalogo: {{ $updatedAt ?? 'sin registro' }}
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 space-y-4">
                <h3 class="text-lg font-semibold">Procedimientos tribunal</h3>

                <form wire:submit="addProcedimiento" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-700 mb-1">Nombre del procedimiento</label>
                        <input type="text" wire:model="procedimientoNombre" class="w-full rounded-md border-gray-300 text-sm" placeholder="Ej: Procedimiento Control de Asistencia">
                        @error('procedimientoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Orden</label>
                        <input type="number" min="1" wire:model="procedimientoOrden" class="w-full rounded-md border-gray-300 text-sm">
                        @error('procedimientoOrden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Archivo</label>
                        <input type="file" wire:model="procedimientoArchivo" class="w-full rounded-md border-gray-300 text-sm">
                        @error('procedimientoArchivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-4">
                        <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="addProcedimiento,procedimientoArchivo">
                            Agregar procedimiento
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Orden</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">URL</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($procedimientos as $i => $item)
                                <tr wire:key="proc-{{ $i }}">
                                    <td class="px-4 py-2">{{ $item['orden'] }}</td>
                                    <td class="px-4 py-2">{{ $item['nombre'] }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ $item['url'] }}" target="_blank" class="text-sky-700 hover:underline">{{ $item['url'] }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" wire:click="removeProcedimiento({{ $i }})" wire:confirm="Se quitara este procedimiento del listado. Continuar?" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin procedimientos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 space-y-4">
                <h3 class="text-lg font-semibold">Documentos</h3>

                <form wire:submit="addDocumento" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-700 mb-1">Nombre del documento</label>
                        <input type="text" wire:model="documentoNombre" class="w-full rounded-md border-gray-300 text-sm" placeholder="Ej: MODELO CAJA CHICA">
                        @error('documentoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Orden</label>
                        <input type="number" min="1" wire:model="documentoOrden" class="w-full rounded-md border-gray-300 text-sm">
                        @error('documentoOrden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Archivo</label>
                        <input type="file" wire:model="documentoArchivo" class="w-full rounded-md border-gray-300 text-sm">
                        @error('documentoArchivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-4">
                        <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="addDocumento,documentoArchivo">
                            Agregar documento
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Orden</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">URL</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($documentos as $i => $item)
                                <tr wire:key="doc-{{ $i }}">
                                    <td class="px-4 py-2">{{ $item['orden'] }}</td>
                                    <td class="px-4 py-2">{{ $item['nombre'] }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ $item['url'] }}" target="_blank" class="text-sky-700 hover:underline">{{ $item['url'] }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" wire:click="removeDocumento({{ $i }})" wire:confirm="Se quitara este documento del listado. Continuar?" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin documentos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 space-y-4">
                <h3 class="text-lg font-semibold">Migracion Unijud</h3>

                <form wire:submit="addTribunal" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-3">
                        <label class="block text-sm text-gray-700 mb-1">Nombre del tribunal</label>
                        <input type="text" wire:model="tribunalNombre" class="w-full rounded-md border-gray-300 text-sm" placeholder="Ej: Juzgado de Garantia de...">
                        @error('tribunalNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:self-end">
                        <button type="submit" class="w-full rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="addTribunal">
                            Agregar tribunal
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($tribunales as $i => $item)
                                <tr wire:key="trib-{{ $i }}">
                                    <td class="px-4 py-2">{{ $item['nombre'] }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" wire:click="removeTribunal({{ $i }})" wire:confirm="Se quitara este tribunal del listado. Continuar?" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center text-gray-500">Sin tribunales cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 space-y-4">
                <h3 class="text-lg font-semibold">Buscador de sentencias</h3>
                <p class="text-sm text-gray-600">Sube un archivo Excel (.xlsx). La importacion reemplaza toda la tabla actual del buscador de sentencias.</p>

                <form wire:submit="importSentencias" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-3">
                        <label class="block text-sm text-gray-700 mb-1">Archivo Excel</label>
                        <input type="file" wire:model="sentenciasExcel" class="w-full rounded-md border-gray-300 text-sm" accept=".xlsx">
                        @error('sentenciasExcel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:self-end">
                        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="importSentencias,sentenciasExcel">
                            Importar Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
