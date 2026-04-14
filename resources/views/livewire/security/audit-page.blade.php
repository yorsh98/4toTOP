<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Seguridad</h2>
</x-slot:header>

<div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="mb-4 text-xl font-semibold text-gray-800">Auditoria</h2>
                    @include('seguridad._tabs')

                    <div class="mb-4 grid gap-2 md:grid-cols-4">
                        <input type="text" wire:model.live.debounce.350ms="q" class="rounded-md border-gray-300 text-sm" placeholder="Buscar actor, accion o entidad">

                        <select wire:model.live="action" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas las acciones</option>
                            @foreach ($actions as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="entity" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas las entidades</option>
                            @foreach ($entities as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>

                        <button type="button" wire:click="$refresh" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800" wire:loading.attr="disabled">
                            Actualizar
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Fecha</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Actor</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Accion</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Entidad</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Antes</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Despues</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($audits as $audit)
                                    <tr wire:key="audit-{{ $audit->id }}">
                                        <td class="px-4 py-2 align-top whitespace-nowrap">
                                            {{ optional($audit->created_at)->format('d-m-Y H:i:s') }}
                                        </td>
                                        <td class="px-4 py-2 align-top">
                                            <div>{{ $audit->actor_name ?: 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $audit->actor_email }}</div>
                                        </td>
                                        <td class="px-4 py-2 align-top">{{ $audit->action }}</td>
                                        <td class="px-4 py-2 align-top">
                                            <div>{{ $audit->entity_type }}</div>
                                            <div class="text-xs text-gray-500">ID: {{ $audit->entity_id ?: '-' }}</div>
                                        </td>
                                        <td class="px-4 py-2 align-top">
                                            <pre class="max-w-xs overflow-auto rounded bg-gray-50 p-2 text-xs">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </td>
                                        <td class="px-4 py-2 align-top">
                                            <pre class="max-w-xs overflow-auto rounded bg-gray-50 p-2 text-xs">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay eventos de auditoria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $audits->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
