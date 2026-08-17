<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administracion" title="Roles" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        <x-flash-notifications />

        <div wire:offline class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Sin conexion. Los cambios no se han guardado.</div>

        @can('navbar.seguridad.editable')
            <form wire:submit="createRole" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="w-full sm:max-w-md">
                        <h3 class="text-lg font-semibold text-slate-900">Crear rol</h3>
                        <p class="mt-1 text-sm text-slate-600">Usa un nombre breve y reconocible para el equipo.</p>
                        <label for="name" class="mt-4 block text-sm font-medium text-slate-700">Nombre del rol</label>
                        <input id="name" name="name" type="text" wire:model="name" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600" placeholder="Ej: Coordinador">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-sky-700 px-4 py-2 text-sm font-medium text-white hover:bg-sky-800 disabled:opacity-50" wire:loading.attr="disabled" wire:target="createRole">
                        <span wire:loading.remove wire:target="createRole">Crear rol</span><span wire:loading wire:target="createRole">Creando...</span>
                    </button>
                </div>
            </form>
        @endcan

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div><h3 class="text-lg font-semibold text-slate-900">Roles de acceso</h3><p class="mt-1 text-sm text-slate-600">{{ $roles->count() }} {{ $roles->count() === 1 ? 'rol registrado' : 'roles registrados' }}</p></div>
                <span wire:loading wire:target="deleteRole" class="text-sm text-slate-500">Actualizando...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Rol</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Usuarios asignados</th>
                                    <th class="px-5 py-3 text-right font-semibold text-slate-700">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($roles as $role)
                                    <tr wire:key="role-{{ $role->id }}">
                                        <td class="px-5 py-4 font-medium text-slate-900">{{ $role->name }}</td>
                                        <td class="px-5 py-4 text-slate-700">{{ $role->users_count }}</td>
                                        <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('seguridad.roles.permissions.edit', $role) }}" wire:navigate class="rounded-md border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-800 hover:bg-sky-100">Configurar permisos</a>
                                            @can('navbar.seguridad.editable')
                                                @if ($role->name === 'SuperAdmin')
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Protegido</span>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="deleteRole({{ $role->id }})"
                                                        wire:confirm="¿Eliminar rol {{ $role->name }}?"
                                                        class="rounded-md border border-rose-200 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                                                        wire:loading.attr="disabled"
                                                        wire:target="deleteRole({{ $role->id }})"
                                                    >
                                                        Eliminar
                                                    </button>
                                                @endif
                                            @endcan
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-500">No hay roles creados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
            </div>
        </section>
    </div>
</div>
