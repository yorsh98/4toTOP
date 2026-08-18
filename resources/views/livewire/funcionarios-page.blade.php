<x-slot:header>
    <x-page-heading eyebrow="Funcionarios · Catalogo global" title="Catalogo global de funcionarios" />
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900 sm:p-6">
                <x-flash-notifications />

                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <section class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <div class="border-b border-sky-100 bg-sky-50 px-4 py-4">
                                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-sky-700">Registro</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ $editingId ? 'Editar funcionario' : 'Nuevo funcionario' }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">Datos disponibles para los modulos del tribunal.</p>
                            </div>

                            <form wire:submit="{{ $editingId ? 'updateFuncionario' : 'createFuncionario' }}" class="space-y-5 p-4" novalidate>
                                <div>
                                    <p class="mb-3 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Identificacion</p>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre completo</label>
                                <input
                                    id="nombre"
                                    name="nombre"
                                    type="text"
                                    wire:model="nombre"
                                    placeholder="Ej: Maria Perez Soto"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                                    required
                                >
                                            @error('nombre')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="run" class="mb-1 block text-sm font-medium text-slate-700">RUN</label>
                                <input
                                    id="run"
                                    name="run"
                                    type="text"
                                    wire:model="run"
                                    placeholder="Ej: 12.345.678-9"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                                >
                                            @error('run')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="rol" class="mb-1 block text-sm font-medium text-slate-700">Rol</label>
                                <select
                                    id="rol"
                                    name="rol"
                                    wire:model="rol"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
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
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-5">
                                    <p class="mb-3 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Contacto</p>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Correo</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    wire:model="email"
                                    placeholder="correo@institucion.cl"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                                    required
                                >
                                            @error('email')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="telefono" class="mb-1 block text-sm font-medium text-slate-700">Telefono</label>
                                <input
                                    id="telefono"
                                    name="telefono"
                                    type="text"
                                    wire:model="telefono"
                                    placeholder="Ej: 990519412"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                                >
                                            @error('telefono')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="fecha_cumpleanos" class="mb-1 block text-sm font-medium text-slate-700">Fecha de cumpleanos</label>
                                <input
                                    id="fecha_cumpleanos"
                                    name="fecha_cumpleanos"
                                    type="date"
                                    wire:model="fechaCumpleanos"
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                                    required
                                >
                                            @error('fechaCumpleanos')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-5">
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-600 focus:ring-offset-2"
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
                                        class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                        wire:click="resetForm"
                                    >
                                        Cancelar edicion
                                    </button>
                                @endif
                            </div>
                            </form>
                        </section>
                    </div>

                    <div class="lg:col-span-9">
                        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Directorio</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-900">Funcionarios registrados</h3>
                                    </div>

                                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Buscar por nombre, RUN, correo, telefono o rol..."
                                    class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-sky-600 focus:ring-sky-600 sm:w-80"
                                >

                                <span class="inline-flex w-fit items-center rounded-full bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-800">
                                    Total: {{ $solicitantes->total() }}
                                </span>
                                    </div>
                                </div>
                            </div>

                        <div class="max-h-[65vh] overflow-x-hidden overflow-y-auto">
                            <table class="w-full table-fixed divide-y divide-gray-200 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr>
                                        <th class="w-[18%] px-4 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Nombre completo</th>
                                        <th class="w-[12%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">RUN</th>
                                        <th class="w-[10%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Rol</th>
                                        <th class="w-[16%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Correo</th>
                                        <th class="w-[13%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Telefono</th>
                                        <th class="w-[11%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Cumpleanos</th>
                                        <th class="w-[9%] px-3 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Accion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($solicitantes as $solicitante)
                                        <tr wire:key="funcionario-{{ $solicitante->id }}" class="transition-colors hover:bg-sky-50/50">
                                            <td class="px-4 py-3 whitespace-nowrap truncate font-medium text-slate-900" title="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap font-medium tabular-nums text-slate-700">{{ $solicitante->run ?: '-' }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap truncate" title="{{ $solicitante->rol_label }}"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $solicitante->rol_label }}</span></td>
                                            <td class="px-3 py-2 whitespace-nowrap truncate" title="{{ $solicitante->email }}">{{ $solicitante->email }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium tabular-nums">{{ $solicitante->telefono ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ optional($solicitante->fecha_cumpleanos)->format('d-m-Y') }}</td>
                                            <td class="px-3 py-3">
                                                <div class="flex justify-end gap-1">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-amber-100 bg-amber-50 text-amber-700 hover:bg-amber-100"
                                                    wire:click="editFuncionario({{ $solicitante->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editFuncionario({{ $solicitante->id }})"
                                                    title="Editar {{ $solicitante->nombre }}"
                                                    aria-label="Editar {{ $solicitante->nombre }}"
                                                >
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-red-100 bg-red-50 text-red-700 hover:bg-red-100"
                                                    wire:click="deleteFuncionario({{ $solicitante->id }})"
                                                    wire:confirm="¿Deseas eliminar este funcionario?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteFuncionario({{ $solicitante->id }})"
                                                    title="Eliminar {{ $solicitante->nombre }}"
                                                    aria-label="Eliminar {{ $solicitante->nombre }}"
                                                >
                                                    <i wire:loading.remove wire:target="deleteFuncionario({{ $solicitante->id }})" class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    <span wire:loading wire:target="deleteFuncionario({{ $solicitante->id }})" class="text-[10px]">...</span>
                                                </button>
                                                </div>
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

                        <div class="border-t border-slate-200 px-4 py-4 sm:px-5">
                            {{ $solicitantes->links() }}
                        </div>
                        </section>
                </div>
            </div>
        </div>
    </div>

</div>
