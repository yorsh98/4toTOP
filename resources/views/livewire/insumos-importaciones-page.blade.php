<x-slot:header>
    <x-page-heading eyebrow="Insumos · Importaciones" title="Importaciones de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <form wire:submit="previsualizar" class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
        <label for="archivo" class="block text-sm font-medium bo-strong">Archivo de stock</label>
        <input id="archivo" type="file" wire:model="archivo" accept=".xls,.xlsx" class="mt-2 block w-full text-sm bo-muted bo-input">
        @error('archivo') <p class="mt-2 text-sm bo-text--danger">{{ $message }}</p> @enderror

        <button type="submit" class="mt-4 rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary" wire:loading.attr="disabled">
            Previsualizar
        </button>
    </form>

    @if ($preview)
        <div class="rounded-xl border bo-border bo-panel p-6 shadow-sm bo-card">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold bo-strong">Resumen de importación</h2>
                    <p class="mt-1 text-sm bo-muted">{{ $preview['filas'] }} lineas, {{ number_format($preview['total_unidades'], 0, ',', '.') }} unidades.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $preview['completo'] ? 'bo-panel-subtle bo-text--ok' : 'bo-panel-subtle bo-text--warn' }}">
                    {{ $preview['completo'] ? 'Reporte completo' : 'Reporte parcial' }}
                </span>
            </div>

            <details class="mt-5 rounded-lg border bo-border bo-panel-subtle p-4">
                <summary class="cursor-pointer font-semibold bo-strong">Revisar los {{ $preview['filas'] }} saldos propuestos</summary>
                <p class="mt-2 text-sm bo-muted">Cada fila compara el stock vigente con el saldo del archivo. La diferencia positiva es la cantidad nueva que se sumará.</p>
                <div class="mt-4 max-h-96 overflow-auto">
                    <table class="min-w-full text-left text-sm bo-table"><thead class="sticky top-0 bo-panel-subtle bo-muted"><tr><th class="px-2 py-2">Código</th><th class="px-2 py-2">Insumo</th><th class="px-2 py-2">Bodega</th><th class="px-2 py-2">Actual</th><th class="px-2 py-2">Propuesto</th><th class="px-2 py-2">Cambio</th></tr></thead><tbody>@foreach ($preview['cambios'] as $cambio)<tr class="border-t bo-border"><td class="px-2 py-2">{{ $cambio['codigo'] }}</td><td class="px-2 py-2">{{ $cambio['nombre'] }}</td><td class="px-2 py-2">{{ $cambio['sector'] }}</td><td class="px-2 py-2">{{ $cambio['actual'] }}</td><td class="px-2 py-2">{{ $cambio['propuesto'] }}</td><td @class(['px-2 py-2 font-semibold', 'bo-text--ok' => $cambio['diferencia'] > 0, 'bo-text--danger' => $cambio['diferencia'] < 0, 'bo-muted' => $cambio['diferencia'] === 0])>{{ $cambio['diferencia'] > 0 ? '+' : '' }}{{ $cambio['diferencia'] }} {{ $cambio['unidad_medida'] }}</td></tr>@endforeach</tbody></table>
                </div>
            </details>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm bo-table">
                    <thead class="border-b bo-muted">
                        <tr><th class="px-2 py-2">Código</th><th class="px-2 py-2">Insumo</th><th class="px-2 py-2">Bodega</th><th class="px-2 py-2">Cantidad</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['muestras'] as $item)
                            <tr class="border-b bo-border"><td class="px-2 py-2">{{ $item['codigo'] }}</td><td class="px-2 py-2">{{ $item['nombre'] }}</td><td class="px-2 py-2">{{ $item['sector'] }}</td><td class="px-2 py-2">{{ $item['cantidad'] }} {{ $item['unidad_medida'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label class="mt-5 flex items-start gap-3 rounded-lg border bo-border bo-panel-subtle p-4 text-sm bo-text--warn"><input type="checkbox" wire:model="confirmarCambios" class="mt-0.5 rounded bo-border bo-text--warn bo-checkbox"><span>Revisé cada saldo propuesto y autorizo actualizar el stock con los valores del archivo.</span></label>
            @error('confirmarCambios')<p class="mt-2 text-sm bo-text--danger">Debe aprobar los saldos antes de importar.</p>@enderror
            <button type="button" wire:click="confirmar" class="mt-5 rounded-md px-4 py-2 text-sm font-semibold bo-btn bo-btn--primary" wire:loading.attr="disabled">
                Confirmar importacion
            </button>
        </div>
    @endif
</section>
