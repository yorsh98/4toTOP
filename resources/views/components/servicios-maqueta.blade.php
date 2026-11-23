@php
    [$horizontal, $vertical] = require resource_path("planos/piso-{$piso}.php");
@endphp
<div x-show="relief" style="display:none" wire:key="architectural-floor-{{ $piso }}">
    <div class="model-scroll" x-ref="stage" :class="{ 'model-fullscreen': fallback, 'model-near': zoom >= 1.25 }" tabindex="0" aria-label="Maqueta 3D. Arrastre para girar; use Control y la rueda para acercar. Las flechas giran y acercan."
        @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)" @pointerleave="if ($event.pointerType === 'mouse') up($event)"
        @click.capture="if (moved) { $event.stopImmediatePropagation(); $event.preventDefault(); moved = false }"
        @wheel.ctrl.prevent="clampZoom(zoom - $event.deltaY * .001)"
        @keydown.left.prevent="orbit -= 10" @keydown.right.prevent="orbit += 10" @keydown.up.prevent="clampZoom(zoom + .1)" @keydown.down.prevent="clampZoom(zoom - .1)" @keydown.escape="if (fallback) fallback = false">
        <button type="button" class="model-exit-fullscreen" @click.stop="fullscreen()">Salir de pantalla completa</button>
        <p class="model-hint"><span class="model-hint-mouse">Arrastra para girar · Ctrl + rueda para acercar</span><span class="model-hint-touch">Arrastra para girar · pellizca para acercar</span></p>
        <div class="model-frame">
            <div class="model-slab" :style="{transform: 'rotateX(' + tilt + 'deg) rotateZ(' + orbit + 'deg)', scale: zoom + ' ' + zoom + ' ' + zoom}">
                <i aria-hidden="true" class="model-slab-back"></i>
                @foreach ($hotspots as $room)
                    <div class="model-room"
                        wire:key="model-room-{{ $piso }}-{{ $room['identificador'] }}"
                        data-selected="{{ (string) $selectedHotspot === (string) $room['identificador'] ? 'true' : 'false' }}"
                        data-inventory="{{ $room['has_data'] ? 'present' : 'empty' }}"
                        data-search="{{ $room['is_map_match'] ? 'match' : ($room['is_map_dimmed'] ? 'dimmed' : '') }}"
                        style="--room-height: {{ $room['activos_count'] > 0 ? round(0.35 + min($room['activos_count'], 10) * 0.09, 2) : 0.12 }}cqw; left:{{ ($room['x']/1.5-20)/914*100 }}%;top:{{ ($room['y']/1.5-27)/583*100 }}%;width:{{ $room['w']/1.5/914*100 }}%;height:{{ $room['h']/1.5/583*100 }}%">
                        <div class="model-room-block">
                            <i class="model-room-side model-room-side-xb" aria-hidden="true"></i><i class="model-room-side model-room-side-yb" aria-hidden="true"></i><i class="model-room-side model-room-side-x" aria-hidden="true"></i><i class="model-room-side model-room-side-y" aria-hidden="true"></i>
                            <button type="button" class="model-room-face"
                                wire:click="selectHotspot(@js($room['identificador']))"
                                aria-label="{{ $room['label'] }}, {{ $room['activos_count'] }} activos"
                                title="{{ $room['label'] }} · {{ $room['activos_count'] }} activos"></button>
                            <span class="model-label" :style="{transform: 'translate(-50%, -50%) translateZ(1.9cqw) rotateZ(' + (-orbit) + 'deg) rotateX(' + (-tilt) + 'deg)'}" aria-hidden="true"><span class="model-label-short">{{ str_replace('_', ' ', $room['identificador']) }}</span><span class="model-label-full">{{ $room['label'] }}</span>@if ($room['activos_count'] > 0) · <b>{{ $room['activos_count'] }}</b>@endif</span>
                        </div>
                    </div>
                @endforeach
                @foreach ([$horizontal, $vertical] as $axis => $lines)
                    @foreach ($lines as $coordinate => $segments)
                        @foreach ($segments as [$start, $end])
                            <i aria-hidden="true" class="model-wall" style="left:{{ (($axis ? $coordinate : $start)-20)/914*100 }}%;top:{{ (($axis ? $start : $coordinate)-27)/583*100 }}%;width:{{ $axis ? '0.32%' : (($end-$start)/914*100).'%' }};height:{{ $axis ? (($end-$start)/583*100).'%' : '0.5%' }}"><b></b><i></i><em></em></i>
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
    <div class="model-legend mt-2 flex flex-wrap gap-4 text-sm" aria-label="Leyenda">
        <span><i class="legend-present"></i> Con registros</span><span><i class="legend-empty"></i> Sin registros</span><span><i class="legend-floor"></i> Pasillos</span><span><i class="legend-selected"></i> Coincide con la búsqueda / Seleccionado</span>
    </div>
</div>
