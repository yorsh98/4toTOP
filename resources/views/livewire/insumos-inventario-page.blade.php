<x-slot:header>
    <x-page-heading eyebrow="Insumos · Inventario" title="Inventario de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card"><p class="text-xs font-semibold uppercase bo-muted">Catálogo</p><p class="mt-1 text-3xl font-bold bo-strong">{{ number_format($insumos->total()) }}</p></div>
        <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card"><p class="text-xs font-semibold uppercase bo-muted">Unidades actuales</p><p class="mt-1 text-3xl font-bold bo-strong">{{ number_format($totalUnidades) }}</p></div>
        <div class="rounded-xl border bo-border bo-panel p-4 shadow-sm bo-card"><p class="text-xs font-semibold uppercase bo-muted">Stock bajo</p><p class="mt-1 text-3xl font-bold {{ $stockBajo ? 'bo-text--warn' : 'bo-strong' }}">{{ number_format($stockBajo) }}</p></div>
    </div>

    <form wire:submit="guardar" class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
        <div class="flex items-center justify-between gap-4"><h2 class="text-lg font-semibold bo-strong">{{ $editingId ? 'Editar insumo' : 'Nuevo insumo' }}</h2>@if ($editingId)<button type="button" wire:click="resetFormulario" class="text-sm font-semibold bo-btn bo-btn--secondary">Cancelar</button>@endif</div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <label class="text-sm font-medium bo-strong">Código<input wire:model="codigo" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></label>
            <label class="text-sm font-medium bo-strong">Nombre<input wire:model="nombre" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></label>
            <label class="text-sm font-medium bo-strong">Stock mínimo<input type="number" min="0" wire:model.number="stock_minimo" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></label>
        </div>
        @foreach (['codigo', 'nombre', 'stock_minimo'] as $field) @error($field)<p class="mt-1 text-sm bo-text--danger">{{ $message }}</p>@enderror @endforeach
        <button type="submit" class="mt-5 rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary">{{ $editingId ? 'Guardar cambios' : 'Crear insumo' }}</button>
    </form>

    @if ($reposicionInsumoId)
        <form wire:submit="registrarReposicion" class="rounded-xl border bo-border bo-panel-subtle p-6 shadow-sm bo-card">
            <div class="flex items-center justify-between gap-4"><h2 class="text-lg font-semibold bo-text--ok">Registrar reposicion</h2><button type="button" wire:click="cerrarReposicion" class="text-sm font-semibold bo-btn bo-btn--secondary">Cerrar</button></div>
            <div class="mt-4 grid gap-4 md:grid-cols-3"><label class="text-sm font-medium">Bodega<select wire:model.number="reposicionSectorId" class="mt-1 w-full rounded-md bo-border text-sm bo-select"><option value="">Seleccione bodega</option>@foreach ($sectores as $sector)<option value="{{ $sector->id }}">{{ $sector->nombre }}</option>@endforeach</select></label><label class="text-sm font-medium">Cantidad<input type="number" min="1" wire:model.number="reposicionCantidad" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></label><label class="text-sm font-medium">Referencia<input wire:model="reposicionObservacion" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></label></div>
            @foreach (['reposicionSectorId', 'reposicionCantidad'] as $field) @error($field)<p class="mt-1 text-sm bo-text--danger">{{ $message }}</p>@enderror @endforeach
            <button type="submit" class="mt-5 rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary">Registrar entrada</button>
        </form>
    @endif

    <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><h2 class="text-lg font-semibold bo-strong">Catálogo y existencias</h2><p class="mt-1 text-sm bo-muted">Los insumos con movimientos se desactivan en lugar de eliminarse.</p></div><div class="flex gap-3"><input type="search" wire:model.live.debounce.300ms="search" placeholder="Código o nombre" class="rounded-md bo-border text-sm bo-input"><select wire:model.live="estadoFiltro" class="rounded-md bo-border text-sm bo-select"><option value="">Todos</option><option value="activos">Activos</option><option value="inactivos">Inactivos</option><option value="bajo">Stock bajo</option></select></div></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm bo-table"><thead class="border-b text-left bo-muted"><tr><th class="p-2">Insumo</th><th class="p-2">Código</th><th class="p-2">Stock</th><th class="p-2">Mínimo</th><th class="p-2">Estado</th><th class="p-2">Acciones</th></tr></thead><tbody class="divide-y">@forelse ($insumos as $insumo)<tr wire:key="insumo-{{ $insumo->id }}"><td class="p-2 font-medium">{{ $insumo->nombre }} <span class="bo-muted">{{ $insumo->unidad_medida }}</span></td><td class="p-2">{{ $insumo->codigo }}</td><td class="p-2 {{ ($insumo->existencias_sum_cantidad ?? 0) <= $insumo->stock_minimo ? 'font-semibold bo-text--warn' : '' }}">{{ $insumo->existencias_sum_cantidad ?? 0 }}</td><td class="p-2">{{ $insumo->stock_minimo }}</td><td class="p-2">{{ $insumo->activo ? 'Activo' : 'Inactivo' }}</td><td class="p-2"><div class="flex gap-2"><button wire:click="editar({{ $insumo->id }})" type="button" class="bo-text--info">Editar</button><button wire:click="abrirReposicion({{ $insumo->id }})" type="button" class="bo-text--ok">Reponer</button><button wire:click="alternarActivo({{ $insumo->id }})" type="button" class="bo-muted">{{ $insumo->activo ? 'Desactivar' : 'Activar' }}</button></div></td></tr>@empty<tr><td colspan="6" class="p-6 text-center bo-muted">No hay insumos para este filtro.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-4">{{ $insumos->links() }}</div>
    </div>

    <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card"><h2 class="text-lg font-semibold bo-strong">Últimos movimientos</h2><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm bo-table"><thead class="border-b text-left bo-muted"><tr><th class="p-2">Fecha</th><th class="p-2">Insumo</th><th class="p-2">Responsable</th><th class="p-2">Asignado a</th><th class="p-2">Tipo</th><th class="p-2">Cantidad</th></tr></thead><tbody class="divide-y">@forelse ($movimientos as $movimiento)<tr><td class="p-2">{{ $movimiento->created_at->format('d-m-Y H:i') }}</td><td class="p-2">{{ $movimiento->insumo->nombre }}</td><td class="p-2">{{ $movimiento->createdBy->name }}</td><td class="p-2">{{ $movimiento->asignadoFuncionario?->nombre ?? $movimiento->asignadoA?->name ?? 'Sin asignar' }}</td><td class="p-2">{{ $movimiento->tipo }}</td><td class="p-2">{{ $movimiento->cantidad }}</td></tr>@empty<tr><td colspan="6" class="p-6 text-center bo-muted">Aún no hay movimientos.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $movimientos->links() }}</div></div>
</section>
