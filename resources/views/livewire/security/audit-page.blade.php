<x-slot:header>
    <x-page-heading eyebrow="Seguridad · Administración" title="Auditoría" />
</x-slot:header>

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        @include('seguridad._tabs')

        <section class="bo-card p-5">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold bo-strong">Registro de auditoría</h3>
                    <p class="mt-1 text-sm bo-muted">Revisa los cambios realizados en Seguridad.</p>
                </div>
                @if ($q !== '' || $action !== '' || $entity !== '')
                    <button type="button" wire:click="$set('q', ''); $set('action', ''); $set('entity', '')" class="text-sm font-medium bo-text--info ">Limpiar filtros</button>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="search" wire:model.live.debounce.350ms="q" class="bo-input w-full text-sm" placeholder="Buscar actor, acción o entidad" aria-label="Buscar en auditoría">
                <select wire:model.live="action" class="bo-select text-sm" aria-label="Filtrar por accion">
                    <option value="">Todas las acciones</option>
                    @foreach ($actions as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
                </select>
                <select wire:model.live="entity" class="bo-select text-sm" aria-label="Filtrar por entidad">
                    <option value="">Todas las entidades</option>
                    @foreach ($entities as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
                </select>
                <button type="button" wire:click="$refresh" class="bo-btn bo-btn--secondary text-sm">Actualizar</button>
            </div>
        </section>

        <section class="bo-card overflow-hidden">
            <div class="flex items-center justify-between border-b bo-border px-5 py-4">
                <h3 class="text-lg font-semibold bo-strong">Eventos</h3>
                <span wire:loading class="text-sm bo-muted">Actualizando resultados...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="bo-table text-sm">
                    <thead class="bo-panel-subtle"><tr><th class="px-5 py-3 text-left font-semibold bo-strong">Fecha</th><th class="px-5 py-3 text-left font-semibold bo-strong">Actor</th><th class="px-5 py-3 text-left font-semibold bo-strong">Acción</th><th class="px-5 py-3 text-left font-semibold bo-strong">Entidad</th><th class="px-5 py-3 text-left font-semibold bo-strong">Cambios</th></tr></thead>
                    <tbody class="">
                        @forelse ($audits as $audit)
                            <tr wire:key="audit-{{ $audit->id }}">
                                <td class="whitespace-nowrap px-5 py-4 bo-muted">{{ optional($audit->created_at)->format('d-m-Y H:i') }}</td>
                                <td class="px-5 py-4"><div class="font-medium bo-strong">{{ $audit->actor_name ?: 'N/A' }}</div><div class="text-xs bo-muted">{{ $audit->actor_email }}</div></td>
                                <td class="px-5 py-4 font-mono text-xs bo-strong">{{ $audit->action }}</td>
                                <td class="px-5 py-4"><div class="font-mono text-xs bo-strong">{{ $audit->entity_type }}</div><div class="text-xs bo-muted">ID: {{ $audit->entity_id ?: '-' }}</div></td>
                                <td class="px-5 py-4"><details><summary class="cursor-pointer text-sm font-medium bo-text--info ">Ver cambios</summary><div class="mt-3 grid gap-2 lg:grid-cols-2"><pre class="overflow-auto rounded-md bo-panel-subtle p-3 text-xs bo-strong">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><pre class="overflow-auto rounded-md bo-panel-subtle p-3 text-xs bo-strong">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm bo-muted">{{ $q !== '' || $action !== '' || $entity !== '' ? 'No hay eventos para los filtros aplicados.' : 'Aún no hay eventos de auditoría.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $audits->links() }}</div>
        </section>
    </div>
</div>
