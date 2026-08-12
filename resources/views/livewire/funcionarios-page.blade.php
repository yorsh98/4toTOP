<x-slot:header>
    <x-page-heading eyebrow="Funcionarios · Catalogo global" title="Catalogo global de funcionarios" />
</x-slot:header>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Este catalogo se utiliza como fuente global para Programacion Diaria, Turnos y Oficio/Libertad.
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-3">
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

            <div class="lg:col-span-9">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Funcionarios registrados</h3>

                            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Buscar por nombre, RUN, correo, telefono o rol..."
                                    class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-80"
                                >

                                <span class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                    Total: {{ $solicitantes->total() }}
                                </span>
                            </div>
                        </div>

                        <div class="max-h-[65vh] overflow-y-auto rounded-lg border border-gray-200">
                            <table class="w-full table-fixed divide-y divide-gray-200 text-sm">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr>
                                        <th class="w-[20%] px-3 py-2 text-left font-semibold text-gray-600">Nombre completo</th>
                                        <th class="w-[13%] px-3 py-2 text-left font-semibold text-gray-600">RUN</th>
                                        <th class="w-[10%] px-3 py-2 text-left font-semibold text-gray-600">Rol</th>
                                        <th class="w-[17%] px-3 py-2 text-left font-semibold text-gray-600">Correo</th>
                                        <th class="w-[14%] px-3 py-2 text-left font-semibold text-gray-600">Telefono</th>
                                        <th class="w-[10%] px-3 py-2 text-left font-semibold text-gray-600">Cumpleanos</th>
                                        <th class="w-[16%] px-3 py-2 text-right font-semibold text-gray-600">Accion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($solicitantes as $solicitante)
                                        <tr wire:key="funcionario-{{ $solicitante->id }}">
                                            <td class="px-3 py-2 whitespace-nowrap truncate" title="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium tabular-nums">{{ $solicitante->run ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap truncate" title="{{ $solicitante->rol_label }}">{{ $solicitante->rol_label }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap truncate" title="{{ $solicitante->email }}">{{ $solicitante->email }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium tabular-nums">{{ $solicitante->telefono ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ optional($solicitante->fecha_cumpleanos)->format('d-m-Y') }}</td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    class="mr-1 inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                                    wire:click="editFuncionario({{ $solicitante->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editFuncionario({{ $solicitante->id }})"
                                                >
                                                    Editar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100"
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
                                                @if (filled($search))
                                                    No hay coincidencias para la busqueda ingresada.
                                                @else
                                                    Aun no hay funcionarios cargados.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $solicitantes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
