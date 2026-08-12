<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administracion" title="Usuarios y roles" />
</x-slot:header>

<div class="py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="mb-4 text-xl font-semibold text-gray-800">Usuarios y Roles</h2>
                    @include('seguridad._tabs')

                    @if (session('ok'))
                        <div class="mb-4 rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                    @endif

                    <div class="mb-4 flex gap-2">
                        <input type="text" wire:model.live.debounce.350ms="q" class="w-full max-w-md rounded-md border-gray-300 text-sm" placeholder="Buscar por nombre o email">
                    </div>

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
                                    <tr wire:key="security-user-{{ $user->id }}">
                                        <td class="px-4 py-2">{{ $user->name }}</td>
                                        <td class="px-4 py-2">{{ $user->email }}</td>
                                        <td class="px-4 py-2">
                                            @if ($user->rejected_at)
                                                <span class="inline-flex rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Rechazado</span>
                                                <div class="mt-1 text-xs text-gray-500">{{ $user->rejection_note ?: 'Sin motivo' }}</div>
                                            @elseif (! $user->approved_at)
                                                <span class="inline-flex rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Pendiente aprobacion</span>
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
                                            <form wire:submit="saveUserRoles({{ $user->id }})" class="space-y-2">
                                                <select wire:model="selectedRoles.{{ $user->id }}" multiple size="4" class="w-64 rounded-md border-gray-300 text-sm">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div>
                                                    <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="saveUserRoles({{ $user->id }})">
                                                        Guardar
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="px-4 py-2 align-top">
                                            <div class="grid gap-2">
                                                @if (! $user->approved_at || $user->rejected_at)
                                                    <button type="button" wire:click="approveUser({{ $user->id }})" class="w-full rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700" wire:loading.attr="disabled" wire:target="approveUser({{ $user->id }})">
                                                        Aprobar usuario
                                                    </button>
                                                @endif

                                                @if (! $user->rejected_at)
                                                    <button type="button" wire:click="rejectUser({{ $user->id }})" class="w-full rounded-md bg-rose-600 px-3 py-2 text-xs font-medium text-white hover:bg-rose-700" wire:loading.attr="disabled" wire:target="rejectUser({{ $user->id }})">
                                                        Rechazar usuario
                                                    </button>
                                                @endif

                                                <button
                                                    type="button"
                                                    wire:click="sendResetPasswordLink({{ $user->id }})"
                                                    class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                    wire:loading.attr="disabled"
                                                    wire:target="sendResetPasswordLink({{ $user->id }})"
                                                    @disabled($user->suspended_at || ! $user->approved_at || $user->rejected_at)
                                                >
                                                    Enviar reset contrasena
                                                </button>

                                                @if ($user->suspended_at)
                                                    <button type="button" wire:click="setUserSuspension({{ $user->id }}, false)" class="w-full rounded-md bg-green-600 px-3 py-2 text-xs font-medium text-white hover:bg-green-700" wire:loading.attr="disabled" wire:target="setUserSuspension({{ $user->id }}, false)">
                                                        Reactivar
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="setUserSuspension({{ $user->id }}, true)" class="w-full rounded-md bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700" wire:loading.attr="disabled" wire:target="setUserSuspension({{ $user->id }}, true)">
                                                        Suspender
                                                    </button>
                                                @endif
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
