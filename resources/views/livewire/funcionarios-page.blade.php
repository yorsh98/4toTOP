<x-slot:header>
    <x-page-heading eyebrow="Funcionarios · Catálogo global" title="Catálogo global de funcionarios" />
</x-slot:header>

<div class="py-2">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bo-card overflow-hidden">
            <div class="bo-funcionarios-container p-4 sm:p-6">
                <x-flash-notifications />

                <div class="bo-funcionarios-layout">
                    <div class="min-w-0">
                        <section class="bo-section overflow-hidden">
                            <div class="bo-panel-heading px-4 py-4">
                                <p class="bo-eyebrow">Registro</p>
                                <h3 class="mt-1 text-lg font-semibold">
                                    {{ $editingId ? 'Editar funcionario' : 'Nuevo funcionario' }}
                                </h3>
                                <p class="mt-1 text-sm">Datos disponibles para los módulos del tribunal.</p>
                            </div>

                            <form wire:submit="{{ $editingId ? 'updateFuncionario' : 'createFuncionario' }}" class="space-y-5 p-4" novalidate>
                                <div>
                                    <p class="mb-3 bo-eyebrow">Identificación</p>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="nombre" class="bo-label mb-1">Nombre completo</label>
                                <input
                                    id="nombre"
                                    name="nombre"
                                    type="text"
                                    wire:model="nombre"
                                    placeholder="Ej.: María Pérez Soto"
                                    class="bo-input w-full text-sm"
                                    required
                                >
                                            @error('nombre')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="run" class="bo-label mb-1">RUN</label>
                                <input
                                    id="run"
                                    name="run"
                                    type="text"
                                    wire:model="run"
                                    placeholder="Ej: 12.345.678-9"
                                    class="bo-input w-full text-sm"
                                >
                                            @error('run')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="rol" class="bo-label mb-1">Rol</label>
                                <select
                                    id="rol"
                                    name="rol"
                                    wire:model="rol"
                                    class="bo-select w-full text-sm"
                                    required
                                >
                                    <option value="">Seleccione un rol</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                            @error('rol')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-5">
                                    <p class="mb-3 bo-eyebrow">Contacto</p>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="email" class="bo-label mb-1">Correo</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    wire:model="email"
                                    placeholder="correo@institucion.cl"
                                    class="bo-input w-full text-sm"
                                    required
                                >
                                            @error('email')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="telefono" class="bo-label mb-1">Teléfono</label>
                                <input
                                    id="telefono"
                                    name="telefono"
                                    type="text"
                                    wire:model="telefono"
                                    placeholder="Ej: 990519412"
                                    class="bo-input w-full text-sm"
                                >
                                            @error('telefono')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="fecha_cumpleanos" class="bo-label mb-1">Fecha de cumpleaños</label>
                                <input
                                    id="fecha_cumpleanos"
                                    name="fecha_cumpleanos"
                                    type="date"
                                    wire:model="fechaCumpleaños"
                                    class="bo-input w-full text-sm"
                                    required
                                >
                                            @error('fechaCumpleaños')
                                                <p class="bo-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-5">
                                <button
                                    type="submit"
                                    class="bo-btn bo-btn--primary w-full"
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
                                        class="bo-btn bo-btn--secondary mt-2 w-full"
                                        wire:click="resetForm"
                                    >
                                        Cancelar edición
                                    </button>
                                @endif
                            </div>
                            </form>
                        </section>
                    </div>

                    <div class="min-w-0">
                        <section class="bo-card overflow-hidden">
                            <div class="bo-panel-heading px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="bo-eyebrow">Directorio</p>
                                        <h3 class="mt-1 text-lg font-semibold">Funcionarios registrados</h3>
                                    </div>

                                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Buscar por nombre, RUN, correo, teléfono o rol..."
                                    class="bo-input w-full text-sm sm:w-80"
                                >

                                <span class="bo-chip w-fit text-xs font-semibold">
                                    Total: {{ $solicitantes->total() }}
                                </span>
                                    </div>
                                </div>
                            </div>

                        <div class="max-h-[65vh] overflow-auto">
                            <table class="bo-table bo-funcionarios-table w-full table-fixed text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr>
                                        <th class="w-[18%] px-4 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Nombre completo</th>
                                        <th class="w-[12%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">RUN</th>
                                        <th class="w-[10%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Rol</th>
                                        <th class="w-[16%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Correo</th>
                                        <th class="w-[13%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Teléfono</th>
                                        <th class="w-[11%] px-3 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Cumpleaños</th>
                                        <th class="w-[9%] px-3 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em]">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="">
                                    @forelse ($solicitantes as $solicitante)
                                        <tr wire:key="funcionario-{{ $solicitante->id }}" class="transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap truncate font-medium" title="{{ $solicitante->nombre }}">{{ $solicitante->nombre }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap font-medium tabular-nums">{{ $solicitante->run ?: '-' }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap truncate" title="{{ $solicitante->rol_label }}"><span class="bo-chip bo-funcionarios-role text-xs font-medium">{{ $solicitante->rol_label }}</span></td>
                                            <td class="px-3 py-2 whitespace-nowrap truncate" title="{{ $solicitante->email }}">{{ $solicitante->email }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium tabular-nums">{{ $solicitante->telefono ?: '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ optional($solicitante->fecha_cumpleanos)->format('d-m-Y') }}</td>
                                            <td class="px-3 py-3">
                                                <div class="flex justify-end gap-1">
                                                <button
                                                    type="button"
                                                    class="bo-btn-icon shrink-0"
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
                                                    class="bo-btn-icon shrink-0"
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
                                            <td colspan="7" class="px-3 py-6 text-center">
                                                @if (filled($search))
                                                    No hay coincidencias para la búsqueda ingresada.
                                                @else
                                                    Aún no hay funcionarios cargados.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t px-4 py-4 sm:px-5">
                            {{ $solicitantes->links() }}
                        </div>
                        </section>
                </div>
            </div>
        </div>
    </div>

</div>
