<div wire:key="audiocut-{{ $format }}" x-data="audioCutter({{ config('conversions.audio_cut_max_segments') }})"
     x-on:change.document="if ($event.target.id === 'conversion-file') load($event.target.files[0])"
     x-on:audio-loaded.window="loaded($event)" x-on:conversion-queued.window="load(null)"
     x-on:livewire-upload-error.window="if (ready) loading = false"
     x-on:livewire-upload-finish.window="if (ready) loading = false" class="min-w-0 space-y-4">
    <p wire:loading wire:target="file" class="text-sm font-medium text-sky-700" role="status">Analizando el audio…</p>
    <div wire:ignore>
        <p x-show="error" x-text="error" style="display: none" class="mb-3 text-sm text-red-700" role="alert"></p>
        <template x-if="ready">
            <div tabindex="0" x-on:keydown="keydown($event)" aria-label="Editor de audio" class="min-w-0 space-y-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700"
                 x-effect="if (hasEditor) { $wire.options.mode; recolor(); }">
                <p x-show="!file" class="text-sm text-slate-600">Sube un audio para ver su onda y marcar los fragmentos.</p>
                <p x-show="loading" class="text-sm text-sky-700" role="status">Preparando la onda…</p>
                <p x-show="hasEditor && !playable" class="text-sm text-amber-800" role="status">Este formato no se puede escuchar en el navegador. Puedes cortarlo igual usando la onda y los tiempos.</p>
                <div x-show="hasEditor" class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Controles de audio">
                        @foreach ([
                            ['play()', 'Reproducir o pausar', 'fa-play', '!playable'],
                            ['playRegion(selected)', 'Reproducir región seleccionada', 'fa-circle-play', '!playable || !selected'],
                            ['addAtCursor()', 'Agregar región en el cursor', 'fa-plus', 'false'],
                            ['splitAtCursor()', 'Dividir región en el cursor', 'fa-scissors', '!regions.length'],
                            ['undo()', 'Deshacer', 'fa-rotate-left', '!history.length'],
                            ['redo()', 'Rehacer', 'fa-rotate-right', '!future.length'],
                            ['zoomBy(2)', 'Acercar', 'fa-magnifying-glass-plus', 'zoom >= 1000'],
                            ['zoomBy(0.5)', 'Alejar', 'fa-magnifying-glass-minus', 'zoom <= 1'],
                        ] as [$action, $label, $icon, $disabled])
                            <button type="button" x-on:click="{{ $action }}" :disabled="{{ $disabled }}" aria-label="{{ $label }}" title="{{ $label }}" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-sky-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-40">
                                <i class="fa-solid {{ $icon }}" @if ($action === 'play()') :class="playing ? 'fa-pause' : 'fa-play'" @endif aria-hidden="true"></i>
                            </button>
                        @endforeach
                        <span class="text-sm font-mono text-slate-700"><span x-text="clock(time)"></span> / <span x-text="clock(duration)"></span></span>
                    </div>
                    <div x-ref="wave" tabindex="0" aria-label="Forma de onda. Arrastra para marcar una región." class="max-w-full overflow-hidden rounded-md border border-slate-300 bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700"></div>
                    <p class="text-sm text-slate-600">Arrastra sobre la onda para marcar regiones. Atajos: Espacio reproduce, Supr quita la región, Ctrl+Z deshace.</p>
                    <p x-show="!regions.length" class="text-sm text-slate-600">No hay regiones marcadas. Arrastra sobre la onda o pulsa «Agregar región en el cursor».</p>
                    <ol class="grid gap-2" aria-label="Regiones marcadas">
                        <template x-for="(region, index) in regions" :key="region.id">
                            <li x-on:click="select(region.id)" class="grid min-w-0 gap-3 rounded-lg border bg-slate-50 p-3 sm:grid-cols-[auto_1fr_1fr_auto_auto] sm:items-end" :class="selected === region.id ? 'border-sky-700' : 'border-slate-300'">
                                <button type="button" x-on:click="select(region.id)" :aria-pressed="selected === region.id" :aria-label="'Seleccionar región ' + (index + 1)" class="min-h-10 rounded px-2 text-sm font-semibold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700" x-text="'Región ' + (index + 1)"></button>
                                <label class="grid min-w-0 gap-1 text-sm font-medium text-slate-800">Inicio
                                    <input type="text" inputmode="decimal" :value="clock(region.start)" :aria-label="'Inicio de región ' + (index + 1)" :aria-invalid="!!invalid[region.id + ':start']" x-on:change="setTime(region.id, 'start', $event.target.value)" class="min-h-10 w-full min-w-0 rounded-md border-slate-300 text-sm focus:border-sky-700 focus:ring-sky-700 aria-[invalid=true]:border-red-700">
                                </label>
                                <label class="grid min-w-0 gap-1 text-sm font-medium text-slate-800">Fin
                                    <input type="text" inputmode="decimal" :value="clock(region.end)" :aria-label="'Fin de región ' + (index + 1)" :aria-invalid="!!invalid[region.id + ':end']" x-on:change="setTime(region.id, 'end', $event.target.value)" class="min-h-10 w-full min-w-0 rounded-md border-slate-300 text-sm focus:border-sky-700 focus:ring-sky-700 aria-[invalid=true]:border-red-700">
                                </label>
                                <span class="py-2 text-sm text-slate-600">Duración: <span x-text="clock(region.end - region.start)"></span></span>
                                <span class="flex gap-2">
                                    <button type="button" x-on:click="playRegion(region.id)" :disabled="!playable" :aria-label="'Reproducir región ' + (index + 1)" class="h-10 w-10 rounded text-sky-800 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-40"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                                    <button type="button" x-on:click.stop="removeRegion(region.id)" :aria-label="'Quitar región ' + (index + 1)" class="h-10 w-10 rounded text-red-700 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                </span>
                            </li>
                        </template>
                    </ol>
                    <details class="text-sm text-slate-600">
                        <summary class="cursor-pointer py-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700">Todos los atajos del editor</summary>
                        <p class="mt-2">Shift+Espacio: escuchar región. Inicio/Fin: mover cursor al extremo. Flechas: mover 1 s (Shift: 10 s). [ y ]: fijar inicio y fin. Ctrl+I: dividir. Ctrl+Z: deshacer. Ctrl+Shift+Z o Ctrl+Y: rehacer. Ctrl+1 / Ctrl+3 / Ctrl+2: acercar, alejar y zoom normal. Los atajos se activan con el foco en el editor y se desactivan al escribir tiempos.</p>
                    </details>
                </div>
            </div>
        </template>
    </div>
    <fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
        <legend class="px-1 text-sm font-semibold text-slate-800">Qué hacer con las regiones</legend>
        <div class="grid gap-2 sm:grid-cols-3" role="group" aria-label="Modo de corte">
            @foreach (['keep' => 'Conservar lo marcado', 'remove' => 'Quitar lo marcado', 'silence' => 'Silenciar lo marcado'] as $mode => $label)
                <button type="button" wire:click="$set('options.mode', '{{ $mode }}')" aria-pressed="{{ $options['mode'] === $mode ? 'true' : 'false' }}" class="min-h-10 rounded-lg border-2 px-3 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 {{ $options['mode'] === $mode ? 'border-sky-700 bg-sky-50 text-[#0A2E5D]' : 'border-slate-300 text-slate-700 hover:border-sky-700' }}">{{ $label }}</button>
            @endforeach
        </div>
        @error('options.mode') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        @error('options.segments') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        @if ($options['mode'] === 'keep')
            <label class="inline-flex min-h-10 items-center gap-2 text-sm text-slate-800"><input type="checkbox" wire:model="options.separate" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Guardar cada región en un archivo aparte</label>
        @endif
        @error('options.separate') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="audio-fade" class="mb-1 block text-sm font-semibold text-slate-800">Fundido</label>
                <select id="audio-fade" wire:model="options.fade_ms" @disabled($options['mode'] === 'silence') class="min-h-10 w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700 disabled:opacity-50">
                    @foreach ([0 => 'Sin fundido', 100 => 'Suave (0,1 s)', 500 => 'Medio (0,5 s)', 2000 => 'Largo (2 s)'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-600">Al unir regiones, cada fundido acorta la duración final. En archivos separados se aplica al inicio y al fin.</p>
                @error('options.fade_ms') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="audio-output" class="mb-1 block text-sm font-semibold text-slate-800">Formato de salida</label>
                <select id="audio-output" wire:model="options.output" class="min-h-10 w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                    @foreach (App\Conversions\AudioCutTool::ENCODERS as $value => $encoder)
                        <option value="{{ $value }}">{{ $encoder['label'] }}</option>
                    @endforeach
                </select>
                @error('options.output') <p class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="inline-flex min-h-10 items-center gap-2 text-sm text-slate-800"><input type="checkbox" wire:model="options.normalize" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Normalizar el volumen</label>
        @error('options.normalize') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
    </fieldset>
    @if ($audioDuration)
        <p class="text-sm text-slate-700">El audio dura {{ App\Services\AudioInspector::clock($audioDuration) }}.</p>
    @endif
</div>
