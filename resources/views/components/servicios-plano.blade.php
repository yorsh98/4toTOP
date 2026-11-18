@props(['piso', 'hotspots'])
<div x-data="{ relief: false, orbit: -28, zoom: 1 }" class="mt-4 min-w-0">
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <div role="group" aria-label="Vista del plano" class="floor-view-switch">
            @foreach (['2D', '3D'] as $mode)
                <button type="button" @click="relief = {{ $mode === '3D' ? 'true' : 'false' }}"
                    :aria-pressed="String({{ $mode === '3D' ? 'relief' : '!relief' }})">{{ $mode }}</button>
            @endforeach
        </div>
            <div x-show="relief" style="display:none" class="flex flex-wrap items-center gap-4 text-sm">
                <label>Girar <input type="range" min="-180" max="180" x-model.number="orbit" :aria-valuetext="orbit + ' grados'"></label>
                <label>Acercar <input type="range" min="0.8" max="2" step=".05" x-model.number="zoom" :aria-valuetext="Math.round(zoom * 100) + '%'"></label>
                <button type="button" class="rounded border px-3 py-1" @click="orbit = -28; zoom = 1">Vista inicial</button>
            </div>
    </div>
    @include('components.servicios-maqueta')
    <div x-show="!relief" class="floor-stage">
        {{ $slot }}
    </div>
</div>
