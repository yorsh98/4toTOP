<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administración" title="Permisos: {{ $role->name }}" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        <x-flash-notifications />
        <div wire:offline class="bo-alert bo-alert--warn rounded-lg px-4 py-3 text-sm">Sin conexion. Los cambios no se han guardado.</div>

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('seguridad.roles.index') }}" wire:navigate class="text-sm font-medium bo-text--info ">&larr; Volver a roles</a>
            @cannot('navbar.seguridad.editable')<span class="bo-chip text-xs font-medium">Solo lectura</span>@endcannot
        </div>

        @if ($role->name === 'SuperAdmin')
            <div class="bo-alert bo-alert--info rounded-lg px-4 py-3 text-sm">SuperAdmin conserva todos los permisos del sistema y no requiere configuración manual.</div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <section class="bo-card p-5">
                <div class="mb-5"><h3 class="text-lg font-semibold bo-strong">Acceso a módulos</h3><p class="mt-1 text-sm bo-muted"><strong>Ver</strong> muestra el módulo. <strong>Gestionar</strong> permite realizar cambios e incluye el acceso de visualización.</p></div>
                <div class=" rounded-lg border bo-border">
                    @foreach ($navbarPermissions as $group)
                        <div class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                            <p class="font-medium bo-strong">{{ $group['label'] }}</p>
                            <div class="flex flex-wrap gap-x-5 gap-y-3">
                                @foreach ($group['permissions'] as $permission)
                                    <label class="inline-flex min-h-6 items-center gap-2 text-sm bo-strong">
                                        <input type="checkbox" wire:model="permissions" value="{{ $permission->name }}" class="bo-checkbox" @disabled($role->name === 'SuperAdmin' || ! auth()->user()->can('navbar.seguridad.editable'))>
                                        <span>{{ str_ends_with($permission->name, '.editable') ? 'Gestionar' : 'Ver' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <details class="bo-card p-5">
                <summary class="cursor-pointer text-sm font-semibold bo-strong">Permisos tecnicos y heredados</summary>
                <div class="mt-5 space-y-4">
                    @foreach ($advancedPermissions as $module => $items)
                        <section class="rounded-lg border bo-border p-4"><h4 class="mb-3 font-medium bo-strong">{{ ucfirst($module) }}</h4><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($items as $permission)
                                <label class="inline-flex min-h-6 items-center gap-2 text-sm bo-strong"><input type="checkbox" wire:model="permissions" value="{{ $permission->name }}" class="bo-checkbox" @disabled($role->name === 'SuperAdmin' || ! auth()->user()->can('navbar.seguridad.editable'))><span>{{ $permission->name }}</span></label>
                            @endforeach
                        </div></section>
                    @endforeach
                </div>
            </details>

            @error('permissions.*')<p class="text-sm bo-text--danger">{{ $message }}</p>@enderror
            @can('navbar.seguridad.editable')
                @if ($role->name !== 'SuperAdmin')
                    <button type="submit" class="bo-btn bo-btn--primary text-sm" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Guardar permisos</span><span wire:loading wire:target="save">Guardando permisos...</span></button>
                @endif
            @endcan
        </form>
    </div>
</div>
