<x-slot:header>
    <x-page-heading eyebrow="Insumos · Importaciones" title="Importaciones de insumos" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />

    <form wire:submit="previsualizar" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <label for="archivo" class="block text-sm font-medium text-slate-700">Archivo de stock</label>
        <input id="archivo" type="file" wire:model="archivo" accept=".xls,.xlsx" class="mt-2 block w-full text-sm text-slate-600">
        @error('archivo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <button type="submit" class="mt-4 rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800" wire:loading.attr="disabled">
            Previsualizar
        </button>
    </form>

    @if ($preview)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Resumen de importacion</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $preview['filas'] }} lineas, {{ number_format($preview['total_unidades'], 0, ',', '.') }} unidades.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $preview['completo'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $preview['completo'] ? 'Reporte completo' : 'Reporte parcial' }}
                </span>
            </div>

            <details class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer font-semibold text-slate-900">Revisar los {{ $preview['filas'] }} saldos propuestos</summary>
                <p class="mt-2 text-sm text-slate-600">Cada fila compara el stock vigente con el saldo del archivo. La diferencia positiva es la cantidad nueva que se sumará.</p>
                <div class="mt-4 max-h-96 overflow-auto">
                    <table class="min-w-full text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-slate-500"><tr><th class="px-2 py-2">Código</th><th class="px-2 py-2">Insumo</th><th class="px-2 py-2">Bodega</th><th class="px-2 py-2">Actual</th><th class="px-2 py-2">Propuesto</th><th class="px-2 py-2">Cambio</th></tr></thead><tbody>@foreach ($preview['cambios'] as $cambio)<tr class="border-t border-slate-200"><td class="px-2 py-2">{{ $cambio['codigo'] }}</td><td class="px-2 py-2">{{ $cambio['nombre'] }}</td><td class="px-2 py-2">{{ $cambio['sector'] }}</td><td class="px-2 py-2">{{ $cambio['actual'] }}</td><td class="px-2 py-2">{{ $cambio['propuesto'] }}</td><td @class(['px-2 py-2 font-semibold', 'text-emerald-700' => $cambio['diferencia'] > 0, 'text-red-700' => $cambio['diferencia'] < 0, 'text-slate-500' => $cambio['diferencia'] === 0])>{{ $cambio['diferencia'] > 0 ? '+' : '' }}{{ $cambio['diferencia'] }} {{ $cambio['unidad_medida'] }}</td></tr>@endforeach</tbody></table>
                </div>
            </details>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b text-slate-500">
                        <tr><th class="px-2 py-2">Codigo</th><th class="px-2 py-2">Insumo</th><th class="px-2 py-2">Bodega</th><th class="px-2 py-2">Cantidad</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['muestras'] as $item)
                            <tr class="border-b border-slate-100"><td class="px-2 py-2">{{ $item['codigo'] }}</td><td class="px-2 py-2">{{ $item['nombre'] }}</td><td class="px-2 py-2">{{ $item['sector'] }}</td><td class="px-2 py-2">{{ $item['cantidad'] }} {{ $item['unidad_medida'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label class="mt-5 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><input type="checkbox" wire:model="confirmarCambios" class="mt-0.5 rounded border-amber-400 text-amber-700 focus:ring-amber-600"><span>Revisé cada saldo propuesto y autorizo actualizar el stock con los valores del archivo.</span></label>
            @error('confirmarCambios')<p class="mt-2 text-sm text-red-600">Debe aprobar los saldos antes de importar.</p>@enderror
            <button type="button" wire:click="confirmar" class="mt-5 rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800" wire:loading.attr="disabled">
                Confirmar importacion
            </button>
        </div>
    @endif
</section>
