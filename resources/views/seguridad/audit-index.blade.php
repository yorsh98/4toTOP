<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Seguridad: Auditoria
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('seguridad._tabs')

                    <form method="GET" action="{{ route('seguridad.auditoria.index') }}" class="mb-4 grid gap-2 md:grid-cols-4">
                        <input type="text" name="q" value="{{ $q }}" class="rounded-md border-gray-300 text-sm" placeholder="Buscar actor, accion o entidad">

                        <select name="action" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas las acciones</option>
                            @foreach ($actions as $item)
                                <option value="{{ $item }}" @selected($action === $item)>{{ $item }}</option>
                            @endforeach
                        </select>

                        <select name="entity" class="rounded-md border-gray-300 text-sm">
                            <option value="">Todas las entidades</option>
                            @foreach ($entities as $item)
                                <option value="{{ $item }}" @selected($entity === $item)>{{ $item }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filtrar</button>
                    </form>

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
                                    <tr>
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
</x-app-layout>
