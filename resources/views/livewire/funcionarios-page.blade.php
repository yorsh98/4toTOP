<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catalogo Global de Funcionarios</h2>
</x-slot:header>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Este catalogo se utiliza como fuente global para Programacion Diaria, Turnos y Oficio/Libertad.
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            {{ $editingId ? 'Editar funcionario' : 'Nuevo funcionario' }}
                        </h3>

                        <form wire:submit="{{ $editingId ? 'updateFuncionario' : 'createFuncionario' }}" class="space-y-4" novalidate>
                            <div>
                                <label for="nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre completo</label>
                                <input
                                    id="nombre"
                                    name="nombre"
                                    type="text"
                                    wire:model="nombre"
                                    placeholder="Ej: Maria Perez Soto"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('nombre')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="run" class="mb-1 block text-sm font-medium text-gray-700">RUN</label>
                                <input
                                    id="run"
                                    name="run"
                                    type="text"
                                    wire:model="run"
                                    placeholder="Ej: 12.345.678-9"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('run')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="rol" class="mb-1 block text-sm font-medium text-gray-700">Rol</label>
                                <select
                                    id="rol"
                                    name="rol"
                                    wire:model="rol"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                    <option value="">Seleccione un rol</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('rol')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    wire:model="email"
                                    placeholder="correo@institucion.cl"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="telefono" class="mb-1 block text-sm font-medium text-gray-700">Telefono</label>
                                <input
                                    id="telefono"
                                    name="telefono"
                                    type="text"
                                    wire:model="telefono"
                                    placeholder="Ej: 990519412"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('telefono')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="fecha_cumpleanos" class="mb-1 block text-sm font-medium text-gray-700">Fecha de cumpleanos</label>
                                <input
                                    id="fecha_cumpleanos"
                                    name="fecha_cumpleanos"
                                    type="date"
                                    wire:model="fechaCumpleanos"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('fechaCumpleanos')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                    wire:loading.attr="disabled"
                                    wire:target="createFuncionario,updateFuncionario"
                                >
                                    <span wire:loading.remove wire:target="createFuncionario,updateFuncionario">
                                        {{ $editingId ? 'Actualizar funcionario' : 'Guardar funcionario' }}
                                    </span>
                                    <span wire:loading wire:target="createFuncionario,updateFuncionario">Guardando...</span>
                                </button>

                                @if ($editingId)
                                    <button
                                        type="button"
                                        class="mt-2 w-full rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                                        wire:click="resetForm"
                                    >
                                        Cancelar edicion
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Funcionarios registrados</h3>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                Total: {{ $solicitantes->count() }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Nombre completo</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">RUN</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Rol</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Correo</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Telefono</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Cumpleanos</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-600">Accion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($solicitantes as $solicitante)
                                        <tr wire:key="funcionario-{{ $solicitante->id }}">
                                            <td class="px-3 py-2">{{ $solicitante->nombre }}</td>
                                            <td class="px-3 py-2">{{ $solicitante->run ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ $solicitante->rol_label }}</td>
                                            <td class="px-3 py-2">{{ $solicitante->email }}</td>
                                            <td class="px-3 py-2">{{ $solicitante->telefono ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ optional($solicitante->fecha_cumpleanos)->format('d-m-Y') }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <button
                                                    type="button"
                                                    class="mr-2 rounded-md bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                                    wire:click="editFuncionario({{ $solicitante->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editFuncionario({{ $solicitante->id }})"
                                                >
                                                    Editar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-md bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                    wire:click="deleteFuncionario({{ $solicitante->id }})"
                                                    wire:confirm="¿Deseas eliminar este funcionario?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteFuncionario({{ $solicitante->id }})"
                                                >
                                                    <span wire:loading.remove wire:target="deleteFuncionario({{ $solicitante->id }})">Eliminar</span>
                                                    <span wire:loading wire:target="deleteFuncionario({{ $solicitante->id }})">Eliminando...</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                                Aun no hay funcionarios cargados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
