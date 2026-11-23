@props(['piso', 'hotspots', 'selectedHotspot' => null])
<div x-data="serviciosPlano()" class="mt-4 min-w-0">
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <div role="group" aria-label="Vista del plano" class="floor-view-switch">
            @foreach (['2D', '3D'] as $mode)
                <button type="button" @click="relief = {{ $mode === '3D' ? 'true' : 'false' }}"
                    :aria-pressed="String({{ $mode === '3D' ? 'relief' : '!relief' }})">{{ $mode }}</button>
            @endforeach
        </div>
            <div x-show="relief" style="display:none" class="model-controls flex flex-wrap items-center gap-3 text-sm">
                <button type="button" @click="orbit -= 90" aria-label="Girar 90 grados a la izquierda">⟲</button>
                <button type="button" @click="orbit += 90" aria-label="Girar 90 grados a la derecha">⟳</button>
                <button type="button" @click="reset()" aria-label="Brújula: volver a la vista inicial" title="Norte · vista inicial">N <span class="inline-block transition-transform" :style="{transform: 'rotate(' + orbit + 'deg)'}" aria-hidden="true">↑</span></button>
                <button type="button" @click="fullscreen()">Pantalla completa</button>
                <label>Girar <input type="range" min="-180" max="180" x-model.number="orbit" :aria-valuetext="orbit + ' grados'"></label>
                <label>Acercar <input type="range" min="0.8" max="2" step=".05" x-model.number="zoom" :aria-valuetext="Math.round(zoom * 100) + '%'"></label>
                <button type="button" class="rounded border px-3 py-1" @click="reset()">Vista inicial</button>
            </div>
    </div>
    <x-servicios-maqueta :piso="$piso" :hotspots="$hotspots" :selected-hotspot="$selectedHotspot" />
    <div x-show="!relief" class="floor-stage">
        {{ $slot }}
    </div>
</div>
