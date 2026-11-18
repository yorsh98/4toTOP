@php
    [$horizontal, $vertical] = require resource_path("planos/piso-{$piso}.php");
@endphp
<div x-show="relief" style="display:none" wire:key="architectural-floor-{{ $piso }}">
    <div class="model-scroll">
        <div class="model-frame" :style="{width: (zoom * 100) + '%'}">
            <div class="model-slab" :style="{transform: 'rotateX(48deg) rotateZ(' + orbit + 'deg)'}">
                <i aria-hidden="true" class="model-slab-back"></i>
                @foreach ($hotspots as $room)
                    <button type="button" class="model-room"
                        wire:key="model-room-{{ $piso }}-{{ $room['identificador'] }}"
                        wire:click="selectHotspot(@js($room['identificador']))"
                        data-inventory="{{ $room['has_data'] ? 'present' : 'empty' }}"
                        data-search="{{ $room['is_map_match'] ? 'match' : ($room['is_map_dimmed'] ? 'dimmed' : '') }}"
                        style="left:{{ ($room['x']/1.5-20)/914*100 }}%;top:{{ ($room['y']/1.5-27)/583*100 }}%;width:{{ $room['w']/1.5/914*100 }}%;height:{{ $room['h']/1.5/583*100 }}%"
                        aria-label="{{ $room['label'] }}, {{ $room['activos_count'] }} activos"
                        title="{{ $room['label'] }}">
                        <span>{{ $room['label'] }}</span>
                    </button>
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
    <div class="mt-2 flex flex-wrap gap-4 text-sm" aria-label="Leyenda">
        <span>🟦 Con registros</span><span>⬜ Sin registros</span><span>🟨 Pasillos</span>
    </div>
</div>
