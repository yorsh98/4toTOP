<div>
    <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-end md:justify-between">
        <div>
            <label class="block mb-1 text-sm text-gray-600">Fecha</label>
            <input type="date" wire:model.lazy="fecha"
                class="px-3 py-2 border border-gray-300 rounded-md focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('audiencias.export.diaria', ['fecha' => $fecha]) }}" target="_blank"
                class="inline-flex items-center justify-center rounded-md px-3 py-2 transition-transform duration-200 ease-out motion-safe:hover:-translate-y-0.5 motion-safe:hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                title="Exportar Excel" aria-label="Exportar Excel">
                <i class="fas fa-file-excel inline-block" style="color:#217346; font-size:32px;"></i>
            </a>
            <button type="button" wire:click="diaAnterior" class="px-3 py-2 border rounded-md hover:bg-gray-50">← Día anterior</button>
            <button type="button" wire:click="hoy" class="px-3 py-2 border rounded-md hover:bg-gray-50">Hoy</button>
            <button type="button" wire:click="diaSiguiente" class="px-3 py-2 border rounded-md hover:bg-gray-50">Día siguiente →</button>
            <button type="button" wire:click="openSend"
                class="px-3 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Enviar por correo
            </button>
        </div>
    </div>

    <livewire:audiencias-diarias-tabla :fecha="$fecha" :wire:key="'audiencias-tabla-'.$fecha" />

    @if ($showSend)
        <livewire:audiencias-diarias-correo :fecha="$fecha" wire:key="audiencias-correo" />
    @endif

    @if ($editId)
        <section class="mt-6 pt-6 border-t">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Editando audiencia</h2>
                <button type="button" wire:click="closeEdit" class="px-3 py-2 border rounded-md hover:bg-gray-50">
                    ← Volver
                </button>
            </div>

            @if ($editTab === 'lecturas')
                <livewire:lecturas-sentencia-batch :edit="$editId" :wire:key="'ls-'.$editId" />
            @elseif ($editTab === 'cortas')
                <livewire:audiencias-cortas-batch :edit="$editId" :wire:key="'cortas-'.$editId" />
            @else
                <livewire:audiencia-form :edit="$editId" :wire:key="'form-'.$editId" />
            @endif
        </section>
    @endif
</div>
