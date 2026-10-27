{{-- "Cortar audio" as a near full-screen dialog in four steps. Alpine owns the dialog and the editor; Livewire owns the
     fields and the job status. wire:ignore.self keeps each morph (wire:poll) from removing the dialog's "open" attribute. --}}
@php
    $steps = [1 => 'Sube el audio', 2 => 'Marca los fragmentos', 3 => 'Elige qué hacer', 4 => 'Corta y descarga'];
    $modes = [
        'keep' => ['Quedarme solo con lo marcado', 'Une los fragmentos y descarta el resto.', ['mark', 'mark']],
        'remove' => ['Quitar lo marcado', 'Saca los fragmentos y une lo que queda.', ['keep', 'keep', 'keep']],
        'silence' => ['Silenciar lo marcado', 'Deja los fragmentos en silencio. La duración no cambia.', ['keep', 'mute', 'keep', 'mute', 'keep']],
    ];
    $remaining = $area === 'frontoffice' ? ($remainingByFormat['audiocut'] ?? 0) : null;
    $pending = $lastJob && in_array($lastJob->status, ['queued', 'processing'], true);
@endphp
<div wire:key="audio-cutter" x-data="audioCutter({{ config('conversions.audio_cut_max_segments') }}, {{ $maxUploadBytes }})"
     x-on:open-audio-cutter.window="open()"
     x-on:change.document="if ($event.target.id === 'conversion-file') load($event.target.files[0])"
     x-on:audio-loaded.window="loaded($event)"
     x-on:conversion-queued.window="submitted = true"
     x-on:livewire-upload-start.window="progress = 0"
     x-on:livewire-upload-progress.window="progress = $event.detail.progress"
     x-on:livewire-upload-error.window="progress = null; if (ready) loading = false"
     x-on:livewire-upload-finish.window="progress = null; if (ready) loading = false">
    <dialog id="audio-cutter" x-ref="dialog" wire:ignore.self class="ac-modal" :class="isOpen && 'is-open'"
            aria-labelledby="ac-title" aria-describedby="ac-step-live" x-on:cancel.prevent="close()">
        <form class="ac-shell" x-on:submit.prevent="submit()" novalidate>
            <header class="ac-head">
                <div class="min-w-0">
                    <h2 id="ac-title" class="ac-title">Cortar audio</h2>
                    <p class="ac-sub">Saca, quita o silencia partes de una grabación.</p>
                </div>
                <button type="button" x-on:click="close()" class="ac-close cm-press" aria-label="Cerrar el cortador (tu avance se conserva)">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </header>

            <nav class="ac-railwrap" aria-label="Pasos para cortar el audio">
                <ol class="ac-rail">
                    @foreach ($steps as $n => $title)
                        <li class="ac-rail-item" :data-state="stepState({{ $n }})">
                            <button type="button" class="ac-rail-btn" x-on:click="go({{ $n }})" :disabled="!canGo({{ $n }})" :aria-current="step === {{ $n }} ? 'step' : null">
                                <span class="ac-dot ac-num" aria-hidden="true"><span class="ac-dot-n">{{ $n }}</span><i class="fa-solid fa-check ac-dot-ok"></i></span>
                                <span class="ac-rail-text">
                                    <span class="ac-rail-title">{{ $title }}</span>
                                    <span class="ac-rail-sum" x-text="summary({{ $n }})"></span>
                                </span>
                            </button>
                            <span class="ac-link" aria-hidden="true"></span>
                        </li>
                    @endforeach
                </ol>
                <div class="ac-rail-compact" aria-hidden="true">
                    <p>Paso <span class="ac-num" x-text="step"></span> de 4 · <span x-text="titles[step]"></span></p>
                    <span class="ac-rail-bar"><span :style="'transform: scaleX(' + step / 4 + ')'"></span></span>
                </div>
            </nav>
            <p id="ac-step-live" class="sr-only" aria-live="polite" x-text="'Paso ' + step + ' de 4: ' + titles[step]"></p>

            <div class="ac-body">
                <div class="ac-stage" :style="'--ac-dir: ' + dir">

                    {{-- 1. Upload --}}
                    <section x-show="step === 1" x-transition:enter="ac-enter" x-transition:enter-start="ac-enter-start" x-transition:enter-end="ac-enter-end" x-transition:leave="ac-leave" x-transition:leave-start="ac-leave-start" x-transition:leave-end="ac-leave-end" class="ac-pane" aria-labelledby="ac-h1">
                        <div class="ac-pane-head">
                            <h3 id="ac-h1" x-ref="h1" tabindex="-1" class="ac-h"><span class="ac-num">1</span>Sube el audio</h3>
                            <p class="ac-lead">Elige la grabación que quieres cortar. Apenas termine de analizarse pasarás al siguiente paso.</p>
                        </div>
                        <div class="ac-narrow">
                            @if ($area === 'frontoffice')
                                <div class="cm-rise" style="--cm-i: 1">
                                    <label for="ac-actor" class="ac-label">Nombre de quien realiza el corte</label>
                                    <input id="ac-actor" type="text" wire:model="actorName" required maxlength="120" autocomplete="name" placeholder="Escribe tu nombre" class="ac-input">
                                    @error('actorName') <p class="ac-error" data-ac-error="1" role="alert">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <label class="ac-drop cm-rise" style="--cm-i: 2" :class="over && 'is-over'" x-on:dragover.prevent="over = true" x-on:dragleave="over = false" x-on:drop.prevent="drop($event)" x-on:change.capture="checkSize($event)">
                                <span class="ac-drop-icon" aria-hidden="true"><i class="fa-solid fa-file-audio"></i></span>
                                <span class="ac-drop-title" x-text="file ? 'Cambiar el audio' : 'Arrastra el audio aquí'"></span>
                                <span class="ac-drop-or">o <span class="ac-drop-link">elige un archivo</span></span>
                                <span class="ac-drop-meta">{{ $types }} · hasta {{ $maxUpload }} y {{ App\Services\AudioInspector::limit(config('conversions.audio_max_seconds')) }}</span>
                                <input id="conversion-file" x-ref="file" type="file" wire:model="file" accept="{{ $accept }}" class="sr-only">
                            </label>
                            <p x-show="tooLarge" style="display: none" class="ac-error" role="alert">El archivo pesa más de {{ $maxUpload }}, el máximo que acepta el servidor. Prueba con una versión comprimida, por ejemplo un MP3.</p>

                            <div x-show="progress !== null" style="display: none" class="ac-upload" role="status">
                                <span class="ac-progress"><span :style="'transform: scaleX(' + (progress ?? 0) / 100 + ')'"></span></span>
                                <span class="ac-num" x-text="'Subiendo… ' + (progress ?? 0) + ' %'"></span>
                            </div>
                            <p wire:loading wire:target="file" class="ac-analyzing cm-shimmer" role="status">Analizando el audio…</p>

                            <div x-show="file && duration" style="display: none" class="ac-filecard cm-rise">
                                <i class="fa-solid fa-wave-square" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="ac-filecard-name" x-text="file?.name"></p>
                                    <p class="ac-filecard-meta ac-num" x-text="'Dura ' + clock(duration)"></p>
                                </div>
                            </div>
                            @error('file') <p class="ac-error" data-ac-error="1" role="alert">{{ $message }}</p> @enderror
                            @error('format') <p class="ac-error" data-ac-error="1" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- 2. Mark fragments --}}
                    <section x-show="step === 2" style="display: none" x-transition:enter="ac-enter" x-transition:enter-start="ac-enter-start" x-transition:enter-end="ac-enter-end" x-transition:leave="ac-leave" x-transition:leave-start="ac-leave-start" x-transition:leave-end="ac-leave-end" class="ac-pane" aria-labelledby="ac-h2">
                        <div class="ac-pane-head">
                            <h3 id="ac-h2" x-ref="h2" tabindex="-1" class="ac-h"><span class="ac-num">2</span>Marca los fragmentos</h3>
                            <p class="ac-lead">Arrastra sobre la onda para marcar cada parte que te interesa. Escúchala y ajusta los tiempos si hace falta.</p>
                        </div>
                        <div wire:ignore>
                            <p x-show="error" x-text="error" style="display: none" class="ac-error" role="alert"></p>
                            <template x-if="ready">
                                <div class="ac-editor">
                                    <div class="ac-mesa" tabindex="0" x-on:keydown="keydown($event)" aria-label="Editor de audio"
                                         x-effect="if (hasEditor) { $wire.options.mode; recolor(); }">
                                        <div class="ac-mesa-top">
                                            <p class="ac-clock ac-num" aria-live="off"><span x-text="clock(time)"></span><span class="ac-clock-total" x-text="' / ' + clock(duration)"></span></p>
                                            <div class="ac-tools" role="group" aria-label="Controles de audio">
                                                @foreach ([
                                                    ['play()', 'Reproducir o pausar (Espacio)', 'fa-play', '!playable'],
                                                    ['playRegion(selected)', 'Escuchar el fragmento seleccionado (Shift+Espacio)', 'fa-circle-play', '!playable || !selected'],
                                                    ['addAtCursor()', 'Agregar un fragmento en el cursor', 'fa-plus', 'false'],
                                                    ['splitAtCursor()', 'Dividir el fragmento en el cursor (Ctrl+I)', 'fa-scissors', '!regions.length'],
                                                    ['undo()', 'Deshacer (Ctrl+Z)', 'fa-rotate-left', '!history.length'],
                                                    ['redo()', 'Rehacer (Ctrl+Shift+Z)', 'fa-rotate-right', '!future.length'],
                                                ] as [$action, $label, $icon, $disabled])
                                                    <button type="button" x-on:click="{{ $action }}" :disabled="{{ $disabled }}" aria-label="{{ $label }}" title="{{ $label }}" class="ac-tool cm-press">
                                                        <i class="fa-solid {{ $icon }}" @if ($action === 'play()') :class="playing ? 'fa-pause' : 'fa-play'" @endif aria-hidden="true"></i>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        <p x-show="hasEditor && !playable" class="ac-mesa-note" role="status">Este formato no se puede escuchar en el navegador. Puedes cortarlo igual usando la onda y los tiempos.</p>
                                        <p x-show="loading" class="ac-mesa-note cm-shimmer" role="status">Preparando la onda…</p>
                                        <div class="ac-wavewrap">
                                            <div x-ref="wave" class="ac-wave" :class="hasEditor && 'is-in'" aria-label="Forma de onda. Arrastra para marcar un fragmento."></div>
                                            <p x-show="hasEditor && !regions.length" class="ac-hint" aria-hidden="true"><i class="fa-solid fa-arrows-left-right"></i> Arrastra sobre la onda para marcar el primer fragmento</p>
                                        </div>
                                        <div class="ac-mesa-foot">
                                            <span>Clic: mover el cursor · Arrastrar: marcar un fragmento</span>
                                            <span class="ac-zoom" role="group" aria-label="Zoom">
                                                <button type="button" x-on:click="zoomBy(0.5)" :disabled="zoom <= 1" class="ac-tool ac-tool-sm cm-press" aria-label="Alejar (Ctrl+3)" title="Alejar (Ctrl+3)"><i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i></button>
                                                <button type="button" x-on:click="zoomBy(2)" :disabled="zoom >= 1000" class="ac-tool ac-tool-sm cm-press" aria-label="Acercar (Ctrl+1)" title="Acercar (Ctrl+1)"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></button>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ac-result">
                                        <div class="ac-result-head">
                                            <p class="ac-result-title">Así quedará tu audio</p>
                                            <p class="ac-result-final">Duración final <b class="ac-num" x-ref="final2" x-text="finalText()"></b></p>
                                        </div>
                                        <div class="ac-strip" aria-hidden="true">
                                            <template x-for="seg in plan().segments" :key="seg.kind + ':' + seg.start + ':' + seg.end">
                                                <span class="ac-seg" :class="'is-' + seg.kind" :style="'left:' + seg.start / duration * 100 + '%;width:' + (seg.end - seg.start) / duration * 100 + '%'"></span>
                                            </template>
                                        </div>
                                        <p class="ac-legend"><span class="ac-key is-keep"></span>Se conserva <span class="ac-key is-cut"></span>Se quita <span class="ac-key is-mute"></span>Se silencia <span class="ac-legend-mode">· Ahora: <span x-text="modeLabel()"></span> (lo cambias en el paso 3)</span></p>
                                    </div>

                                    <div class="ac-frags">
                                        <p class="ac-frags-title">Fragmentos <span class="ac-count ac-num" x-ref="count" x-text="regions.length"></span></p>
                                        <p x-show="!regions.length" class="ac-empty">Todavía no marcas fragmentos. Arrastra sobre la onda o usa <i class="fa-solid fa-plus" aria-hidden="true"></i> para agregar uno donde está el cursor.</p>
                                        <ol class="ac-fraglist" aria-label="Fragmentos marcados">
                                            <template x-for="(region, index) in regions" :key="region.id">
                                                <li x-on:click="select(region.id)" class="ac-frag cm-rise" :class="selected === region.id && 'is-selected'">
                                                    <button type="button" x-on:click="select(region.id)" :aria-pressed="selected === region.id" :aria-label="'Seleccionar fragmento ' + (index + 1)" class="ac-frag-n ac-num" x-text="index + 1"></button>
                                                    <label class="ac-frag-field">Inicio
                                                        <input type="text" inputmode="decimal" :value="clock(region.start)" :aria-label="'Inicio del fragmento ' + (index + 1)" :aria-invalid="!!invalid[region.id + ':start']" x-on:change="setTime(region.id, 'start', $event.target.value)" class="ac-input ac-num">
                                                    </label>
                                                    <label class="ac-frag-field">Fin
                                                        <input type="text" inputmode="decimal" :value="clock(region.end)" :aria-label="'Fin del fragmento ' + (index + 1)" :aria-invalid="!!invalid[region.id + ':end']" x-on:change="setTime(region.id, 'end', $event.target.value)" class="ac-input ac-num">
                                                    </label>
                                                    <span class="ac-frag-len ac-num" x-text="clock(region.end - region.start)"></span>
                                                    <span class="ac-frag-actions">
                                                        <button type="button" x-on:click.stop="playRegion(region.id)" :disabled="!playable" :aria-label="'Escuchar el fragmento ' + (index + 1)" class="ac-icon-btn cm-press"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                                                        <button type="button" x-on:click.stop="removeRegion(region.id)" :aria-label="'Quitar el fragmento ' + (index + 1)" class="ac-icon-btn is-danger cm-press"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                                    </span>
                                                </li>
                                            </template>
                                        </ol>
                                    </div>
                                    <details class="ac-shortcuts">
                                        <summary>Atajos de teclado (con el foco en la onda)</summary>
                                        <p>Espacio: reproducir o pausar. Shift+Espacio: escuchar el fragmento. Inicio y Fin: ir a los extremos. Flechas: mover 1 s (con Shift, 10 s). [ y ]: fijar el inicio o el fin del fragmento en el cursor. Ctrl+I: dividir. Supr: quitar el fragmento. Ctrl+Z y Ctrl+Shift+Z: deshacer y rehacer. Ctrl+1 y Ctrl+3: acercar y alejar.</p>
                                    </details>
                                </div>
                            </template>
                        </div>
                        @error('options.segments') <p class="ac-error" data-ac-error="2" role="alert">{{ $message }}</p> @enderror
                    </section>

                    {{-- 3. Choose what to do --}}
                    <section x-show="step === 3" style="display: none" x-transition:enter="ac-enter" x-transition:enter-start="ac-enter-start" x-transition:enter-end="ac-enter-end" x-transition:leave="ac-leave" x-transition:leave-start="ac-leave-start" x-transition:leave-end="ac-leave-end" class="ac-pane" aria-labelledby="ac-h3">
                        <div class="ac-pane-head">
                            <h3 id="ac-h3" x-ref="h3" tabindex="-1" class="ac-h"><span class="ac-num">3</span>Elige qué hacer</h3>
                            <p class="ac-lead">Cada opción muestra cómo quedaría tu audio y cuánto duraría.</p>
                        </div>
                        <div class="ac-modes" role="radiogroup" aria-labelledby="ac-h3">
                            @foreach ($modes as $mode => [$title, $hint, $after])
                                <label class="ac-mode cm-rise" style="--cm-i: {{ $loop->index }}">
                                    <input type="radio" name="ac-mode" value="{{ $mode }}" wire:model="options.mode" class="sr-only">
                                    <span class="ac-mode-diagram" aria-hidden="true">
                                        <span class="ac-mini"><span class="is-keep"></span><span class="is-mark"></span><span class="is-keep"></span><span class="is-mark"></span><span class="is-keep"></span></span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <span class="ac-mini ac-mini-after">@foreach ($after as $piece)<span class="is-{{ $piece }}"></span>@endforeach</span>
                                    </span>
                                    <span class="ac-mode-title">{{ $title }}</span>
                                    <span class="ac-mode-hint">{{ $hint }}</span>
                                    <span class="ac-mode-final">Resultado: <b class="ac-num" x-text="finalText('{{ $mode }}')"></b></span>
                                </label>
                            @endforeach
                        </div>
                        @error('options.mode') <p class="ac-error" data-ac-error="3" role="alert">{{ $message }}</p> @enderror

                        <label x-show="$wire.options.mode === 'keep'" class="ac-check">
                            <input type="checkbox" wire:model="options.separate">
                            <span>Guardar cada fragmento en un archivo aparte <span class="ac-muted">(si son varios, se descargan juntos en un ZIP)</span></span>
                        </label>
                        @error('options.separate') <p class="ac-error" data-ac-error="3" role="alert">{{ $message }}</p> @enderror

                        {{-- wire:ignore.self: the root's wire:poll re-renders every 5 s and would close it; submit() opens it on an error. --}}
                        <details class="ac-advanced" wire:ignore.self>
                            <summary>Opciones avanzadas <span class="ac-muted">· fundido, formato y volumen</span></summary>
                            <div class="ac-advanced-grid">
                                <div>
                                    <label for="audio-fade" class="ac-label">Fundido</label>
                                    <select id="audio-fade" wire:model="options.fade_ms" :disabled="$wire.options.mode === 'silence'" class="ac-input">
                                        @foreach ([0 => 'Sin fundido', 100 => 'Suave (0,1 s)', 500 => 'Medio (0,5 s)', 2000 => 'Largo (2 s)'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="ac-help">Suaviza las uniones para que no se escuche un salto. Cada unión acorta un poco el resultado.</p>
                                    @error('options.fade_ms') <p class="ac-error" data-ac-error="3" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="audio-output" class="ac-label">Formato del archivo</label>
                                    <select id="audio-output" wire:model="options.output" class="ac-input">
                                        @foreach (App\Conversions\AudioCutTool::ENCODERS as $value => $encoder)
                                            <option value="{{ $value }}">{{ $encoder['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <p class="ac-help">MP3 sirve para casi todo. WAV y FLAC conservan la calidad original, pero pesan más.</p>
                                    @error('options.output') <p class="ac-error" data-ac-error="3" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <label class="ac-check">
                                <input type="checkbox" wire:model="options.normalize">
                                <span>Normalizar el volumen <span class="ac-muted">(iguala el volumen si la grabación quedó baja o dispareja)</span></span>
                            </label>
                            @error('options.normalize') <p class="ac-error" data-ac-error="3" role="alert">{{ $message }}</p> @enderror
                        </details>

                        <div class="ac-result" wire:ignore>
                            <div class="ac-result-head">
                                <p class="ac-result-title">Así quedará tu audio</p>
                                <p class="ac-result-final">Duración final <b class="ac-num" x-ref="final3" x-text="finalText()"></b></p>
                            </div>
                            <div class="ac-strip" aria-hidden="true">
                                <template x-for="seg in plan().segments" :key="seg.kind + ':' + seg.start + ':' + seg.end">
                                    <span class="ac-seg" :class="'is-' + seg.kind" :style="'left:' + seg.start / duration * 100 + '%;width:' + (seg.end - seg.start) / duration * 100 + '%'"></span>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- 4. Cut and download --}}
                    <section x-show="step === 4" style="display: none" x-transition:enter="ac-enter" x-transition:enter-start="ac-enter-start" x-transition:enter-end="ac-enter-end" x-transition:leave="ac-leave" x-transition:leave-start="ac-leave-start" x-transition:leave-end="ac-leave-end" class="ac-pane" aria-labelledby="ac-h4">
                        <div class="ac-pane-head">
                            <h3 id="ac-h4" x-ref="h4" tabindex="-1" class="ac-h"><span class="ac-num">4</span>Corta y descarga</h3>
                            <p class="ac-lead">Revisa el resumen. Si algo no está bien, vuelve a cualquier paso desde la barra de arriba.</p>
                        </div>
                        <div class="ac-narrow">
                            <dl class="ac-summary cm-rise">
                                <div><dt>Audio original</dt><dd><span x-text="file?.name"></span> · <span class="ac-num" x-text="clock(duration)"></span></dd></div>
                                <div><dt>Qué se hará</dt><dd x-text="actionText()"></dd></div>
                                <div><dt>Resultado</dt><dd><span x-text="resultName()"></span> · <span class="ac-num" x-text="finalText()"></span></dd></div>
                            </dl>

                            @if ($lastJob)
                                <div wire:key="ac-job-{{ $lastJob->token }}-{{ $lastJob->status }}" class="ac-status cm-rise" role="status">
                                    @if ($pending)
                                        <div wire:poll.2s></div>
                                        <p class="ac-status-title">{{ $lastJob->status === 'queued' ? 'En espera…' : 'Cortando el audio…' }}</p>
                                        <span class="ac-indet" aria-hidden="true"><span></span></span>
                                        <p class="ac-help">Puedes cerrar el cortador: el resultado también aparecerá en «Archivos de esta sesión».</p>
                                    @elseif ($lastJob->status === 'ready' && $lastJob->expires_at?->isFuture())
                                        <p class="ac-status-title is-ok"><span class="ac-done" aria-hidden="true"><i class="fa-solid fa-check"></i></span>Listo</p>
                                        @if ($lastJob->notice)<p class="ac-help">{{ $lastJob->notice }}</p>@endif
                                        <a href="{{ route('conversions.download', $lastJob) }}" class="ac-primary cm-press cm-rise" style="--cm-i: 1"><i class="fa-solid fa-download" aria-hidden="true"></i>Descargar {{ App\Conversions\ConversionTool::downloadLabel(pathinfo($lastJob->result_name, PATHINFO_EXTENSION)) }}</a>
                                    @elseif ($lastJob->status === 'failed')
                                        <p class="ac-status-title is-bad"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>No se pudo cortar el audio</p>
                                        <p class="ac-error">{{ $lastJob->error_message }}</p>
                                    @else
                                        <p class="ac-status-title">La descarga venció. Vuelve a cortar el audio.</p>
                                    @endif
                                </div>
                                @unless ($pending)
                                    <div class="ac-after">
                                        <button type="button" x-on:click="editAgain({{ $lastJob->status === 'failed' ? 3 : 2 }})" class="ac-secondary cm-press">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>{{ $lastJob->status === 'failed' ? 'Volver al paso 3' : 'Volver a marcar este audio' }}
                                        </button>
                                        <button type="button" x-on:click="startOver()" class="ac-secondary cm-press"><i class="fa-solid fa-rotate" aria-hidden="true"></i>Cortar otro audio</button>
                                    </div>
                                @endunless
                            @else
                                <div class="ac-go">
                                    <p class="ac-help">{{ $area === 'frontoffice' ? ($remaining === 1 ? 'Te queda 1 uso' : 'Te quedan '.$remaining.' usos').' de esta herramienta en esta sesión.' : 'Sin límite de uso para tu cuenta.' }}</p>
                                    <button type="submit" class="ac-primary ac-cut cm-press" :class="submitting && 'is-busy'" :aria-busy="submitting" :disabled="submitting" @disabled($area === 'frontoffice' && $remaining === 0)>
                                        <span class="ac-cut-idle"><i class="fa-solid fa-scissors" aria-hidden="true"></i>Cortar audio</span>
                                        <span class="ac-cut-busy" aria-hidden="true"><i class="fa-solid fa-circle-notch fa-spin"></i>Enviando…</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>

            <footer class="ac-foot">
                <button type="button" x-show="step > 1" x-on:click="go(step - 1)" class="ac-secondary cm-press"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Atrás</button>
                <span class="ac-foot-step" x-text="'Paso ' + step + ' de 4'"></span>
                <button type="button" x-show="step < 4" x-on:click="go(step + 1)" :disabled="!canGo(step + 1)" class="ac-primary cm-press">
                    <span x-text="'Siguiente: ' + titles[step + 1]"></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
                <p x-show="step < 4 && !canGo(step + 1)" class="ac-foot-why" x-text="whyLocked(step + 1)"></p>
            </footer>
        </form>
    </dialog>
</div>
