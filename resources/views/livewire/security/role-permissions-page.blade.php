<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administracion" title="Permisos de rol" />
</x-slot:header>

<div class="py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="mb-4 text-xl font-semibold text-gray-800">Permisos de Rol ({{ $role->name }})</h2>
                    @include('seguridad._tabs')

                    @if (session('ok'))
                        <div class="mb-4 rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                    @endif

                    <div class="mb-4">
                        <a href="{{ route('seguridad.roles.index') }}" wire:navigate class="text-sm text-sky-700 hover:underline">← Volver a roles</a>
                    </div>

                    <form wire:submit="save">
                        <div class="space-y-4">
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-base font-semibold">Permisos por Navbar</h3>
                                <p class="mb-4 text-xs text-gray-600">
                                    `visible` muestra la pestana. `editable` permite editar y ejecutar acciones del modulo.
                                </p>

                                <div class="space-y-3">
                                    @foreach ($navbarPermissions as $group)
                                        <div class="rounded-md border p-3">
                                            <div class="mb-2 font-medium text-sm">{{ $group['label'] }}</div>
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                @foreach ($group['permissions'] as $permission)
                                                    <label class="inline-flex items-center gap-2 text-sm">
                                                        <input
                                                            type="checkbox"
                                                            wire:model="permissions"
                                                            value="{{ $permission->name }}"
                                                            class="rounded border-gray-300"
                                                        >
                                                        <span>{{ str_ends_with($permission->name, '.editable') ? 'editable' : 'visible' }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <details class="rounded-lg border p-4">
                                <summary class="cursor-pointer select-none text-sm font-semibold text-gray-700">Permisos avanzados (legacy)</summary>

                                <div class="mt-4 space-y-4">
                                    @foreach ($advancedPermissions as $module => $items)
                                        <div class="rounded-lg border p-4">
                                            <h3 class="mb-3 text-base font-semibold">{{ ucfirst($module) }}</h3>
                                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                @foreach ($items as $permission)
                                                    <label class="inline-flex items-center gap-2 text-sm">
                                                        <input
                                                            type="checkbox"
                                                            wire:model="permissions"
                                                            value="{{ $permission->name }}"
                                                            class="rounded border-gray-300"
                                                        >
                                                        <span>{{ $permission->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>

                        @error('permissions.*')
                            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-6">
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="save">
                                Guardar permisos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
