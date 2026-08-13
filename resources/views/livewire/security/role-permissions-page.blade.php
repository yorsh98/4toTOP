<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administracion" title="Permisos: {{ $role->name }}" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        @if (session('ok'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('ok') }}</div>@endif
        @if (session('error'))<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ session('error') }}</div>@endif
        <div wire:offline class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Sin conexion. Los cambios no se han guardado.</div>

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('seguridad.roles.index') }}" wire:navigate class="text-sm font-medium text-sky-700 hover:text-sky-900">&larr; Volver a roles</a>
            @cannot('navbar.seguridad.editable')<span class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Solo lectura</span>@endcannot
        </div>

        @if ($role->name === 'SuperAdmin')
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">SuperAdmin conserva todos los permisos del sistema y no requiere configuracion manual.</div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5"><h3 class="text-lg font-semibold text-slate-900">Acceso a modulos</h3><p class="mt-1 text-sm text-slate-600"><strong>Ver</strong> muestra el modulo. <strong>Gestionar</strong> permite realizar cambios e incluye el acceso de visualizacion.</p></div>
                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                    @foreach ($navbarPermissions as $group)
                        <div class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                            <p class="font-medium text-slate-900">{{ $group['label'] }}</p>
                            <div class="flex flex-wrap gap-x-5 gap-y-3">
                                @foreach ($group['permissions'] as $permission)
                                    <label class="inline-flex min-h-6 items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" wire:model="permissions" value="{{ $permission->name }}" class="rounded border-slate-300 text-sky-700 shadow-sm focus:ring-sky-600" @disabled($role->name === 'SuperAdmin' || ! auth()->user()->can('navbar.seguridad.editable'))>
                                        <span>{{ str_ends_with($permission->name, '.editable') ? 'Gestionar' : 'Ver' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <details class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <summary class="cursor-pointer text-sm font-semibold text-slate-800">Permisos tecnicos y heredados</summary>
                <div class="mt-5 space-y-4">
                    @foreach ($advancedPermissions as $module => $items)
                        <section class="rounded-lg border border-slate-200 p-4"><h4 class="mb-3 font-medium text-slate-900">{{ ucfirst($module) }}</h4><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($items as $permission)
                                <label class="inline-flex min-h-6 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="permissions" value="{{ $permission->name }}" class="rounded border-slate-300 text-sky-700 shadow-sm focus:ring-sky-600" @disabled($role->name === 'SuperAdmin' || ! auth()->user()->can('navbar.seguridad.editable'))><span>{{ $permission->name }}</span></label>
                            @endforeach
                        </div></section>
                    @endforeach
                </div>
            </details>

            @error('permissions.*')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            @can('navbar.seguridad.editable')
                @if ($role->name !== 'SuperAdmin')
                    <button type="submit" class="rounded-md bg-sky-700 px-4 py-2 text-sm font-medium text-white hover:bg-sky-800 disabled:opacity-50" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Guardar permisos</span><span wire:loading wire:target="save">Guardando permisos...</span></button>
                @endif
            @endcan
        </form>
    </div>
</div>
