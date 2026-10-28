<x-slot:header>
    <x-page-heading eyebrow="Insumos · Gestión" title="Gestión de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <div class="grid gap-6 xl:grid-cols-[1fr_1.2fr]">
        <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
            <h2 class="text-lg font-semibold bo-strong">Solicitudes pendientes</h2>
            <div class="mt-4 space-y-3">
                @forelse ($solicitudes as $solicitud)
                    <button type="button" wire:click="seleccionarSolicitud({{ $solicitud->id }})" class="block w-full rounded-lg border p-4 text-left {{ $selectedSolicitudId === $solicitud->id ? 'bo-border bo-panel-subtle' : 'bo-border ' }}">
                        <span class="font-semibold bo-strong">Solicitud #{{ $solicitud->id }}</span>
                        <span class="ml-2 text-sm bo-muted">{{ $solicitud->solicitante->name }}</span>
                        <span class="mt-1 block text-xs font-semibold uppercase bo-muted">{{ $solicitud->estado }}</span>
                    </button>
                @empty
                    <p class="text-sm bo-muted">No hay solicitudes pendientes.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $solicitudes->links() }}</div>
        </div>

        <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
            @if ($selectedSolicitud)
                <h2 class="text-lg font-semibold bo-strong">Entrega de solicitud #{{ $selectedSolicitud->id }}</h2>
                <p class="mt-1 text-sm bo-muted">Solicitante: {{ $selectedSolicitud->solicitante->name }}</p>
                <div class="mt-4 space-y-4">
                    @foreach ($selectedSolicitud->detalles as $detalle)
                        @php($entregado = $detalle->movimientos->where('tipo', 'asignacion')->sum('cantidad'))
                        @php($pendiente = $detalle->cantidad_solicitada - $entregado)
                        <div class="rounded-lg border bo-border p-4" wire:key="entrega-{{ $detalle->id }}">
                            <p class="font-medium bo-strong">{{ $detalle->insumo->nombre }}</p>
                            <p class="mt-1 text-sm bo-muted">Pendiente: {{ $pendiente }} {{ $detalle->insumo->unidad_medida }}</p>
                            @if ($pendiente > 0)
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <select wire:model.number="entregas.{{ $detalle->id }}.sector_id" class="rounded-md bo-border text-sm bo-select">
                                        <option value="">Seleccione bodega</option>
                                        @foreach ($detalle->insumo->existencias->where('cantidad', '>', 0) as $existencia)
                                            <option value="{{ $existencia->sector_id }}">{{ $existencia->sector->nombre }} ({{ $existencia->cantidad }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="0" max="{{ $pendiente }}" wire:model.number="entregas.{{ $detalle->id }}.cantidad" class="rounded-md bo-border text-sm bo-input">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" wire:click="entregar" class="rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary">Registrar entrega</button>
                    @if ($selectedSolicitud->estado === 'pendiente')
                        <input type="text" wire:model="motivoRechazo" placeholder="Motivo de rechazo" class="rounded-md bo-border text-sm bo-input">
                        <button type="button" wire:click="rechazar" class="rounded-md border bo-border px-4 py-2 text-sm font-semibold bo-btn bo-btn--danger">Rechazar</button>
                    @endif
                </div>
            @else
                <p class="text-sm bo-muted">Seleccione una solicitud para registrar una entrega o rechazo.</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
        <h2 class="text-lg font-semibold bo-strong">Asignación directa</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <select wire:model.number="directRecipientId" class="rounded-md bo-border text-sm bo-select"><option value="">Persona asignada</option>@foreach ($funcionarios as $funcionario)<option value="{{ $funcionario->id }}">{{ $funcionario->nombre }}</option>@endforeach</select>
            <select wire:model.number="directInsumoId" class="rounded-md bo-border text-sm bo-select"><option value="">Insumo</option>@foreach ($insumos as $insumo)<option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>@endforeach</select>
            <div class="flex gap-2"><input type="number" min="1" wire:model.number="directCantidad" class="min-w-0 flex-1 rounded-md bo-border text-sm bo-input"><button type="button" wire:click="agregarAsignacionDirecta" class="rounded-md border bo-border px-3 text-sm font-semibold bo-btn bo-btn--secondary">Agregar</button></div>
        </div>
        @error('directInsumoId') <p class="mt-2 text-sm bo-text--danger">{{ $message }}</p> @enderror

        <div class="mt-4 space-y-2">
            @foreach ($directCart as $key => $item)
                @php($insumo = $insumos->firstWhere('id', $item['insumo_id']))
                <div class="flex items-center justify-between rounded-md bo-panel-subtle px-3 py-2 text-sm" wire:key="direct-{{ $key }}">
                    <span>{{ $insumo?->nombre }} · {{ $item['cantidad'] }}</span>
                    <button type="button" wire:click="quitarAsignacionDirecta('{{ $key }}')" class="font-semibold bo-btn bo-btn--secondary">Quitar</button>
                </div>
            @endforeach
        </div>

        <textarea wire:model="directObservacion" rows="2" maxlength="5000" placeholder="Observación opcional" class="mt-4 w-full rounded-md bo-border text-sm bo-input"></textarea>
        <button type="button" wire:click="asignarDirecto" class="mt-3 rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary">Registrar asignacion directa</button>
    </div>
</section>
