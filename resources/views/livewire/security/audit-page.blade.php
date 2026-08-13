<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administracion" title="Auditoria" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Registro de auditoria</h3>
                    <p class="mt-1 text-sm text-slate-600">Revisa los cambios realizados en Seguridad.</p>
                </div>
                @if ($q !== '' || $action !== '' || $entity !== '')
                    <button type="button" wire:click="$set('q', ''); $set('action', ''); $set('entity', '')" class="text-sm font-medium text-sky-700 hover:text-sky-900">Limpiar filtros</button>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="search" wire:model.live.debounce.350ms="q" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600" placeholder="Buscar actor, accion o entidad" aria-label="Buscar en auditoria">
                <select wire:model.live="action" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600" aria-label="Filtrar por accion">
                    <option value="">Todas las acciones</option>
                    @foreach ($actions as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
                </select>
                <select wire:model.live="entity" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600" aria-label="Filtrar por entidad">
                    <option value="">Todas las entidades</option>
                    @foreach ($entities as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
                </select>
                <button type="button" wire:click="$refresh" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Actualizar</button>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Eventos</h3>
                <span wire:loading class="text-sm text-slate-500">Actualizando resultados...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left font-semibold text-slate-700">Fecha</th><th class="px-5 py-3 text-left font-semibold text-slate-700">Actor</th><th class="px-5 py-3 text-left font-semibold text-slate-700">Accion</th><th class="px-5 py-3 text-left font-semibold text-slate-700">Entidad</th><th class="px-5 py-3 text-left font-semibold text-slate-700">Cambios</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($audits as $audit)
                            <tr wire:key="audit-{{ $audit->id }}">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ optional($audit->created_at)->format('d-m-Y H:i') }}</td>
                                <td class="px-5 py-4"><div class="font-medium text-slate-900">{{ $audit->actor_name ?: 'N/A' }}</div><div class="text-xs text-slate-500">{{ $audit->actor_email }}</div></td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-700">{{ $audit->action }}</td>
                                <td class="px-5 py-4"><div class="font-mono text-xs text-slate-700">{{ $audit->entity_type }}</div><div class="text-xs text-slate-500">ID: {{ $audit->entity_id ?: '-' }}</div></td>
                                <td class="px-5 py-4"><details><summary class="cursor-pointer text-sm font-medium text-sky-700 hover:text-sky-900">Ver cambios</summary><div class="mt-3 grid gap-2 lg:grid-cols-2"><pre class="overflow-auto rounded-md bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><pre class="overflow-auto rounded-md bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">{{ $q !== '' || $action !== '' || $entity !== '' ? 'No hay eventos para los filtros aplicados.' : 'Aun no hay eventos de auditoria.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $audits->links() }}</div>
        </section>
    </div>
</div>
