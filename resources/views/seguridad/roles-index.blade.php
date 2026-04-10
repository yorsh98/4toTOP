<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Seguridad: Roles
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('seguridad._tabs')

                    @if (session('ok'))
                        <div class="mb-4 rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                    @endif

                    <div class="mb-6 rounded-lg border p-4">
                        <h3 class="mb-3 text-base font-semibold">Crear nuevo rol</h3>
                        <form action="{{ route('seguridad.roles.store') }}" method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="w-full sm:w-80">
                                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre del rol</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-md border-gray-300 text-sm" placeholder="Ej: Coordinador">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
                                Crear rol
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Rol</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Usuarios</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($roles as $role)
                                    <tr>
                                        <td class="px-4 py-2">{{ $role->name }}</td>
                                        <td class="px-4 py-2">{{ $role->users_count }}</td>
                                        <td class="px-4 py-2">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('seguridad.roles.permissions.edit', $role) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                                    Permisos
                                                </a>
                                                @if ($role->name !== 'SuperAdmin')
                                                    <form action="{{ route('seguridad.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('¿Eliminar rol {{ $role->name }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay roles disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
