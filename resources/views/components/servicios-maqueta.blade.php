<div x-show="relief" style="display:none" wire:key="architectural-floor-{{ $piso }}" x-data="serviciosMaqueta()">
    <div
        class="model-scroll"
        x-ref="stage"
        :class="{ 'model-fullscreen': fallback }"
        tabindex="0"
        aria-label="Maqueta 3D. Arrastre para girar; use Control y la rueda para acercar. Las flechas giran y acercan."
    >
        <button type="button" class="model-exit-fullscreen" @click.stop="fullscreen()">Salir de pantalla completa</button>
        <p class="model-hint">
            <span class="model-hint-mouse">Arrastra para girar · Ctrl + rueda para acercar</span>
            <span class="model-hint-touch">Arrastra para girar · pellizca para acercar</span>
        </p>
        <div class="model-frame" wire:ignore><div class="model-canvas" x-ref="canvas"></div></div>
        <script type="application/json" x-ref="data">{!! json_encode($maqueta->toArray(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <div class="model-access" x-ref="rooms" aria-label="Salas de la maqueta">
            @foreach ($maqueta->salas as $room)
                <button
                    type="button"
                    wire:key="model-room-{{ $piso }}-{{ $room->identificador }}"
                    wire:click="selectHotspot(@js($room->identificador))"
                    aria-label="{{ $room->label }}, {{ $room->activos }} activos"
                    data-id="{{ $room->identificador }}"
                    data-selected="{{ $room->seleccionada ? 'true' : 'false' }}"
                    data-inventory="{{ $room->conInventario ? 'present' : 'empty' }}"
                    data-search="{{ $room->coincide ? 'match' : ($room->atenuada ? 'dimmed' : '') }}"
                    data-count="{{ $room->activos }}"
                    data-height="{{ $room->altura() }}"
                >{{ $room->label }}</button>
            @endforeach
        </div>
    </div>
    <div class="model-legend mt-2 flex flex-wrap gap-4 text-sm" aria-label="Leyenda">
        <span><i class="legend-present"></i> Con registros</span>
        <span><i class="legend-empty"></i> Sin registros</span>
        <span><i class="legend-floor"></i> Pasillos</span>
        <span><i class="legend-selected"></i> Coincide con la búsqueda / Seleccionado</span>
    </div>
</div>
