<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administración" title="Roles" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        <x-flash-notifications />

        <div wire:offline class="bo-alert bo-alert--warn rounded-lg px-4 py-3 text-sm">Sin conexion. Los cambios no se han guardado.</div>

        @can('navbar.seguridad.editable')
            <form wire:submit="createRole" class="bo-card p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="w-full sm:max-w-md">
                        <h3 class="text-lg font-semibold bo-strong">Crear rol</h3>
                        <p class="mt-1 text-sm bo-muted">Usa un nombre breve y reconocible para el equipo.</p>
                        <label for="name" class="mt-4 block text-sm font-medium bo-strong">Nombre del rol</label>
                        <input id="name" name="name" type="text" wire:model="name" class="mt-1 bo-input w-full text-sm" placeholder="Ej: Coordinador">
                                @error('name')
                                    <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p>
                                @enderror
                    </div>
                    <button type="submit" class="bo-btn bo-btn--primary text-sm" wire:loading.attr="disabled" wire:target="createRole">
                        <span wire:loading.remove wire:target="createRole">Crear rol</span><span wire:loading wire:target="createRole">Creando...</span>
                    </button>
                </div>
            </form>
        @endcan

        <section class="bo-card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b bo-border px-5 py-4">
                <div><h3 class="text-lg font-semibold bo-strong">Roles de acceso</h3><p class="mt-1 text-sm bo-muted">{{ $roles->count() }} {{ $roles->count() === 1 ? 'rol registrado' : 'roles registrados' }}</p></div>
                <span wire:loading wire:target="deleteRole" class="text-sm bo-muted">Actualizando...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="bo-table text-sm">
                    <thead class="bo-panel-subtle">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold bo-strong">Rol</th>
                                    <th class="px-5 py-3 text-left font-semibold bo-strong">Usuarios asignados</th>
                                    <th class="px-5 py-3 text-right font-semibold bo-strong">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class=" bo-panel">
                                @forelse ($roles as $role)
                                    <tr wire:key="role-{{ $role->id }}">
                                        <td class="px-5 py-4 font-medium bo-strong">{{ $role->name }}</td>
                                        <td class="px-5 py-4 bo-strong">{{ $role->users_count }}</td>
                                        <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('seguridad.roles.permissions.edit', $role) }}" wire:navigate aria-label="Configurar permisos de {{ $role->name }}" class="bo-btn-icon text-xs">Configurar permisos</a>
                                            @can('navbar.seguridad.editable')
                                                @if ($role->name === 'SuperAdmin')
                                                    <span class="bo-chip text-xs font-medium">Protegido</span>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="deleteRole({{ $role->id }})"
                                                        wire:confirm="¿Eliminar rol {{ $role->name }}?"
                                                        aria-label="Eliminar rol {{ $role->name }}"
                                                        class="bo-btn-icon bo-text--danger text-xs"
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
                                        <td colspan="3" class="px-5 py-12 text-center text-sm bo-muted">No hay roles creados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
            </div>
        </section>
    </div>
</div>
