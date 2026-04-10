<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Seguridad: Usuarios y Roles
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

                    <form method="GET" action="{{ route('seguridad.users.index') }}" class="mb-4 flex gap-2">
                        <input type="text" name="q" value="{{ $q }}" class="w-full max-w-md rounded-md border-gray-300 text-sm" placeholder="Buscar por nombre o email">
                        <button type="submit" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Buscar</button>
                    </form>

                    <div class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Usuario</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Email</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Estado</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Roles actuales</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Asignar roles</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-2">{{ $user->name }}</td>
                                        <td class="px-4 py-2">{{ $user->email }}</td>
                                        <td class="px-4 py-2">
                                            @if ($user->rejected_at)
                                                <span class="inline-flex rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Rechazado</span>
                                                <div class="mt-1 text-xs text-gray-500">{{ $user->rejection_note ?: 'Sin motivo' }}</div>
                                            @elseif (! $user->approved_at)
                                                <span class="inline-flex rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Pendiente aprobación</span>
                                            @elseif ($user->suspended_at)
                                                <span class="inline-flex rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Suspendido</span>
                                                <div class="mt-1 text-xs text-gray-500">{{ $user->suspension_reason ?: 'Sin motivo' }}</div>
                                            @else
                                                <span class="inline-flex rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Activo</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ $user->roles->pluck('name')->join(', ') ?: 'Sin roles' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <form action="{{ route('seguridad.users.roles.update', $user) }}" method="POST" class="space-y-2">
                                                @csrf
                                                @method('PUT')
                                                <select name="roles[]" multiple size="4" class="w-64 rounded-md border-gray-300 text-sm">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}" @selected($user->roles->contains('name', $role->name))>
                                                            {{ $role->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div>
                                                    <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700">
                                                        Guardar
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="px-4 py-2 align-top">
                                            <div class="grid gap-2">
                                                @if (! $user->approved_at || $user->rejected_at)
                                                    <form action="{{ route('seguridad.users.approve', $user) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                                                            Aprobar usuario
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (! $user->rejected_at)
                                                    <form action="{{ route('seguridad.users.reject', $user) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full rounded-md bg-rose-600 px-3 py-2 text-xs font-medium text-white hover:bg-rose-700">
                                                            Rechazar usuario
                                                        </button>
                                                    </form>
                                                @endif

                                                <form action="{{ route('seguridad.users.send-reset-password', $user) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700" @disabled($user->suspended_at || ! $user->approved_at || $user->rejected_at)>
                                                        Enviar reset contraseña
                                                    </button>
                                                </form>

                                                <form action="{{ route('seguridad.users.suspension', $user) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    @if ($user->suspended_at)
                                                        <input type="hidden" name="suspend" value="0">
                                                        <button type="submit" class="w-full rounded-md bg-green-600 px-3 py-2 text-xs font-medium text-white hover:bg-green-700">
                                                            Reactivar
                                                        </button>
                                                    @else
                                                        <input type="hidden" name="suspend" value="1">
                                                        <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700">
                                                            Suspender
                                                        </button>
                                                    @endif
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay usuarios.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
