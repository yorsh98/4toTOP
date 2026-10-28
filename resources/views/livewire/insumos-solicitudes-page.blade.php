<x-slot:header>
    <x-page-heading eyebrow="Insumos · Solicitudes" title="Solicitudes de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
            <label for="search" class="sr-only">Buscar insumo</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre" class="w-full rounded-md bo-border text-sm shadow-sm bo-input">

            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($catalogo as $insumo)
                    <div class="flex items-center justify-between gap-4 py-3" wire:key="catalogo-{{ $insumo->id }}">
                        <div>
                            <p class="font-medium bo-strong">{{ $insumo->nombre }}</p>
                            <p class="text-sm bo-muted">{{ $insumo->existencias_sum_cantidad ?? 0 }} {{ $insumo->unidad_medida }} disponibles</p>
                        </div>
                        <button type="button" wire:click="agregar({{ $insumo->id }})" class="rounded-md border bo-border px-3 py-1.5 text-sm font-semibold bo-text--info ">Agregar</button>
                    </div>
                @empty
                    <p class="py-6 text-sm bo-muted">No hay insumos disponibles.</p>
                @endforelse
            </div>
            @if ($catalogo->hasPages())
                <div class="mt-5 flex flex-col gap-3 border-t bo-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm bo-muted">Mostrando {{ $catalogo->firstItem() }} a {{ $catalogo->lastItem() }} de {{ $catalogo->total() }} insumos</p>
                    {{ $catalogo->links('livewire.pagination-bootstrap-no-summary', ['scrollTo' => false]) }}
                </div>
            @endif
        </div>

        <form wire:submit="solicitar" class="h-fit rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
            <h2 class="font-semibold bo-strong">Solicitud</h2>
            @error('cart') <p class="mt-2 text-sm bo-text--danger">{{ $message }}</p> @enderror

            <div class="mt-4 space-y-3">
                @forelse ($cartInsumos as $insumo)
                    <div class="flex items-center gap-2" wire:key="cart-{{ $insumo->id }}">
                        <label class="min-w-0 flex-1 text-sm bo-strong">{{ $insumo->nombre }}</label>
                        <input type="number" min="1" step="1" wire:model.number="cart.{{ $insumo->id }}" class="w-16 rounded-md bo-border text-sm bo-input">
                        <button type="button" wire:click="quitar({{ $insumo->id }})" class="text-sm font-medium bo-text--danger">Quitar</button>
                    </div>
                @empty
                    <p class="text-sm bo-muted">Agregue insumos desde el catálogo.</p>
                @endforelse
            </div>

            <label for="firma" class="mt-5 block text-sm font-medium bo-strong">Firma</label>
            <select id="firma" wire:model="firma" class="mt-1 w-full rounded-md bo-border text-sm bo-select">@foreach ($firmas as $firma)<option value="{{ $firma }}">{{ $firma }}</option>@endforeach</select>
            <label for="observacion" class="mt-5 block text-sm font-medium bo-strong">Observación</label>
            <textarea id="observacion" wire:model="observacion" rows="3" maxlength="5000" class="mt-1 w-full rounded-md bo-border text-sm bo-input"></textarea>
            <button type="submit" class="mt-4 w-full rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary" wire:loading.attr="disabled">Enviar solicitud</button>
        </form>
    </div>

    <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
        <h2 class="text-lg font-semibold bo-strong">Mis solicitudes</h2>
        <div class="mt-4 space-y-4">
            @forelse ($solicitudes as $solicitud)
                <article class="rounded-lg border bo-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-semibold bo-strong">Solicitud #{{ $solicitud->id }}</p>
                        <span class="rounded-full bo-panel-subtle px-3 py-1 text-xs font-semibold uppercase bo-strong">{{ $solicitud->estado }}</span>
                    </div>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm bo-muted">
                        @foreach ($solicitud->detalles as $detalle)
                            <li>{{ $detalle->insumo->nombre }}: {{ $detalle->cantidad_solicitada }} solicitados, {{ $detalle->movimientos->where('tipo', 'asignacion')->sum('cantidad') }} entregados</li>
                        @endforeach
                    </ul>
                    @if ($solicitud->observacion)
                        <p class="mt-3 text-sm bo-muted">{{ $solicitud->observacion }}</p>
                    @endif
                    @if (in_array($solicitud->estado, ['pendiente', 'parcial'], true))
                        <button type="button" wire:click="cancelar({{ $solicitud->id }})" class="mt-4 text-sm font-semibold bo-text--danger">Cancelar solicitud</button>
                    @endif
                </article>
            @empty
                <p class="text-sm bo-muted">Aún no tiene solicitudes.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $solicitudes->links() }}</div>
    </div>
</section>
