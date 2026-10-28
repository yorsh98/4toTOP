<div>
    <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-end md:justify-between">
        <div>
            <label class="bo-label block mb-1 text-sm bo-muted">Fecha</label>
            <input type="date" wire:model.lazy="fecha"
                class="bo-input px-3 py-2 border bo-border rounded-md focus:outline-none focus:ring-2 ">
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('audiencias.export.diaria', ['fecha' => $fecha]) }}" target="_blank"
                class="inline-flex items-center justify-center rounded-md px-3 py-2 transition-transform duration-200 ease-out motion-safe:hover:-translate-y-0.5 motion-safe:hover:scale-105 focus:outline-none focus-visible:ring-2 "
                title="Exportar Excel" aria-label="Exportar Excel">
                <i class="fas fa-file-excel inline-block bo-text--ok text-2xl"></i>
            </a>
            <button type="button" wire:click="diaAnterior" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md ">← Día anterior</button>
            <button type="button" wire:click="hoy" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md ">Hoy</button>
            <button type="button" wire:click="diaSiguiente" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md ">Día siguiente →</button>
            <button type="button" wire:click="openSend"
                class="bo-btn bo-btn--primary px-3 py-2   rounded-md focus:outline-none focus:ring-2 ">
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
                <button type="button" wire:click="closeEdit" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md ">
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
