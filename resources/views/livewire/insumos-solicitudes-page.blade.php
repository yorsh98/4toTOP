<x-slot:header>
    <x-page-heading eyebrow="Insumos · Solicitudes" title="Solicitudes de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <label for="search" class="sr-only">Buscar insumo</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600">

            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($catalogo as $insumo)
                    <div class="flex items-center justify-between gap-4 py-3" wire:key="catalogo-{{ $insumo->id }}">
                        <div>
                            <p class="font-medium text-slate-900">{{ $insumo->nombre }}</p>
                            <p class="text-sm text-slate-500">{{ $insumo->existencias_sum_cantidad ?? 0 }} {{ $insumo->unidad_medida }} disponibles</p>
                        </div>
                        <button type="button" wire:click="agregar({{ $insumo->id }})" class="rounded-md border border-cyan-700 px-3 py-1.5 text-sm font-semibold text-cyan-700 hover:bg-cyan-50">Agregar</button>
                    </div>
                @empty
                    <p class="py-6 text-sm text-slate-500">No hay insumos disponibles.</p>
                @endforelse
            </div>
            @if ($catalogo->hasPages())
                <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Mostrando {{ $catalogo->firstItem() }} a {{ $catalogo->lastItem() }} de {{ $catalogo->total() }} insumos</p>
                    {{ $catalogo->links('livewire.pagination-bootstrap-no-summary', ['scrollTo' => false]) }}
                </div>
            @endif
        </div>

        <form wire:submit="solicitar" class="h-fit rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Solicitud</h2>
            @error('cart') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 space-y-3">
                @forelse ($cartInsumos as $insumo)
                    <div class="flex items-center gap-2" wire:key="cart-{{ $insumo->id }}">
                        <label class="min-w-0 flex-1 text-sm text-slate-700">{{ $insumo->nombre }}</label>
                        <input type="number" min="1" step="1" wire:model.number="cart.{{ $insumo->id }}" class="w-16 rounded-md border-slate-300 text-sm">
                        <button type="button" wire:click="quitar({{ $insumo->id }})" class="text-sm font-medium text-red-700">Quitar</button>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Agregue insumos desde el catalogo.</p>
                @endforelse
            </div>

            <label for="firma" class="mt-5 block text-sm font-medium text-slate-700">Firma</label>
            <select id="firma" wire:model="firma" class="mt-1 w-full rounded-md border-slate-300 text-sm">@foreach ($firmas as $firma)<option value="{{ $firma }}">{{ $firma }}</option>@endforeach</select>
            <label for="observacion" class="mt-5 block text-sm font-medium text-slate-700">Observacion</label>
            <textarea id="observacion" wire:model="observacion" rows="3" maxlength="5000" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
            <button type="submit" class="mt-4 w-full rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800" wire:loading.attr="disabled">Enviar solicitud</button>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Mis solicitudes</h2>
        <div class="mt-4 space-y-4">
            @forelse ($solicitudes as $solicitud)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-semibold text-slate-900">Solicitud #{{ $solicitud->id }}</p>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{{ $solicitud->estado }}</span>
                    </div>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        @foreach ($solicitud->detalles as $detalle)
                            <li>{{ $detalle->insumo->nombre }}: {{ $detalle->cantidad_solicitada }} solicitados, {{ $detalle->movimientos->where('tipo', 'asignacion')->sum('cantidad') }} entregados</li>
                        @endforeach
                    </ul>
                    @if ($solicitud->observacion)
                        <p class="mt-3 text-sm text-slate-600">{{ $solicitud->observacion }}</p>
                    @endif
                    @if (in_array($solicitud->estado, ['pendiente', 'parcial'], true))
                        <button type="button" wire:click="cancelar({{ $solicitud->id }})" class="mt-4 text-sm font-semibold text-red-700">Cancelar solicitud</button>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-500">Aun no tiene solicitudes.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $solicitudes->links() }}</div>
    </div>
</section>
