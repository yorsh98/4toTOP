@if ($area === 'backoffice')
    <x-slot:header>
        <x-page-heading eyebrow="Herramientas · Backoffice" title="Convertir archivos">
            <x-slot:actions>
                @can('conversiones.ver_registro')
                    <a href="{{ route('conversions.history') }}" class="{{ $area === 'frontoffice' ? 'text-sm font-semibold text-sky-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700' : 'text-sm font-semibold hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 bo-text--info' }}">Ver registro</a>
                @endcan
            </x-slot:actions>
        </x-page-heading>
    </x-slot:header>
@endif

<div class="{{ $area === 'frontoffice' ? 'pb-6 fo-conv' : 'py-2' }}" {{ $jobs->contains(fn ($job) => in_array($job->status, ['queued', 'processing'])) ? 'wire:poll.2s' : 'wire:poll.5s' }}>
    <div class="{{ $area === 'frontoffice' ? 'mx-auto space-y-6 w-full lg:space-y-8 fo-conv-main' : 'max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8' }}">
        @if ($area === 'backoffice')
            <x-flash-notifications />
        @endif

        @if ($area === 'frontoffice')
            <x-public.page-head title="Convertir archivos" lead="Convierte, organiza y edita tus archivos en línea." />
        @endif

        <section class="{{ $area === 'frontoffice' ? 'overflow-hidden bg-white fo-panel' : 'bo-card bo-section p-6' }}" aria-labelledby="conversion-title">
            @if ($area === 'frontoffice')
            <div class="{{ $area === 'frontoffice' ? 'fo-conv-heading pb-5' : 'bo-panel-heading px-6 py-6 sm:px-8' }}">
                <p class="text-xs font-semibold uppercase tracking-wide {{ $area === 'frontoffice' ? 'fo-conv-eyebrow' : 'bo-eyebrow' }}">Convertir · Organizar · Optimizar · Editar</p>
                <h2 id="conversion-title" class="mt-2 text-xl font-semibold">Elige qué quieres hacer con tus archivos</h2>
                <p class="mt-1 text-sm {{ $area === 'frontoffice' ? 'fo-hint' : 'bo-muted' }}">Tus archivos se eliminan automáticamente después del plazo de descarga.</p>
            </div>
            @else
                <h3 id="conversion-title" class="text-lg font-semibold bo-strong">Elige qué quieres hacer con tus archivos</h3>
                <p class="mt-1 text-sm bo-muted">Tus archivos se eliminan automáticamente después del plazo de descarga.</p>
            @endif

            <div class="space-y-7 {{ $area === 'frontoffice' ? 'pt-6' : 'mt-5' }}">
                <div class="grid gap-5" role="group" aria-label="Herramienta">
                    @foreach (collect($tools)->groupBy(fn ($tool) => $tool->config['group'], preserveKeys: true) as $group => $groupTools)
                        <div>
                            <h3 class="{{ $area === 'frontoffice' ? 'mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500' : 'mb-2 text-xs font-semibold uppercase tracking-wide bo-muted' }}">{{ $group }}</h3>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($groupTools as $option => $optionTool)
                                    <button type="button" @if ($option === 'audiocut' && $format === 'audiocut') x-on:click="$dispatch('open-audio-cutter')" @else wire:click="selectFormat('{{ $option }}')" @endif aria-pressed="{{ $format === $option ? 'true' : 'false' }}" class="{{ $area === 'frontoffice' ? 'flex items-center justify-between gap-3 rounded-xl border-2 p-4 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 '.($format === $option ? 'border-sky-700 bg-sky-50 text-[#0A2E5D]' : 'border-slate-200 bg-white text-slate-700 hover:border-sky-300') : 'bo-card bo-tool flex items-center justify-between gap-3 p-4 text-left '.($format === $option ? 'is-active' : '') }}">
                                        <span class="flex items-center gap-3"><i class="fa-solid {{ $optionTool->config['icon'] }} text-xl text-sky-700" aria-hidden="true"></i><span class="font-semibold">{{ $optionTool->label() }}</span></span>
                                        @if ($area === 'frontoffice')<span class="{{ $area === 'frontoffice' ? 'shrink-0 text-xs font-medium text-slate-500' : 'shrink-0 text-xs font-medium bo-muted' }}">{{ $remainingByFormat[$option] }} de {{ config('conversions.public_limit') }}</span>@endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="convert" class="grid gap-5">
                    @if ($area === 'frontoffice' && $format !== 'audiocut')
                        <div>
                            <label for="actor-name" class="{{ $area === 'frontoffice' ? 'mb-1.5 block text-sm font-semibold text-slate-800' : 'mb-1.5 block text-sm font-semibold bo-strong' }}">Nombre de quien realiza la conversión</label>
                            <input id="actor-name" type="text" wire:model="actorName" required maxlength="120" autocomplete="name" placeholder="Escribe tu nombre" class="{{ $area === 'frontoffice' ? 'w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700' : 'w-full rounded-lg text-sm bo-panel-subtle' }}">
                            @error('actorName') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @php($maxUpload = Illuminate\Support\Number::fileSize($maxUploadBytes, maxPrecision: 1))
                    @php($accept = collect($tool->config['accept'])->map(fn ($extension) => '.'.$extension)->push($tool->acceptsPdf() ? 'application/pdf' : null)->filter()->implode(','))
                    @php($types = collect($tool->config['accept'])->diff($tool->config['aliases'] ?? [])->map(fn ($extension) => strtoupper($extension))->join(', ', ' o '))
                    @if ($tool->multiple())
                        {{-- Uploaded one by one so the total is not bound by post_max_size, and each file is checked as it arrives. --}}
                        <div wire:key="upload-multiple-{{ $format }}" x-data="{
                                uploading: 0,
                                tooLarge: [],
                                async add(event) {
                                    const files = [...event.target.files];
                                    event.target.value = '';
                                    this.tooLarge = files.filter(file => file.size > {{ $maxUploadBytes }}).map(file => file.name);
                                    for (const file of files.filter(file => file.size <= {{ $maxUploadBytes }})) {
                                        this.uploading++;
                                        await new Promise(resolve => this.$wire.upload('incomingFile', file, resolve, resolve));
                                        this.uploading--;
                                    }
                                },
                            }">
                            <label for="multiple-files" class="{{ $area === 'frontoffice' ? 'mb-1.5 block text-sm font-semibold text-slate-800' : 'mb-1.5 block text-sm font-semibold bo-strong' }}">Archivos {{ $types }}</label>
                            <input id="multiple-files" type="file" multiple accept="{{ $accept }}" x-on:change="add($event)" class="{{ $area === 'frontoffice' ? 'block w-full rounded-lg border border-dashed border-sky-300 bg-sky-50/60 p-4 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0A2E5D] file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700' : 'bo-input block w-full text-sm' }}">
                            <p class="{{ $area === 'frontoffice' ? 'mt-2 text-xs text-slate-600' : 'mt-2 text-xs bo-muted' }}">{{ ($tool->config['min_files'] ?? 1) > 1 ? 'Entre '.$tool->config['min_files'].' y ' : 'Hasta ' }}{{ $tool->config['max_files'] }} archivos · máximo {{ $maxUpload }} cada uno y {{ $tool->config['max_total_kb'] / 1024 }} MB en total. Puedes seleccionar varios a la vez o agregarlos en varias tandas.</p>
                            <p x-show="uploading > 0" style="display: none" class="{{ $area === 'frontoffice' ? 'mt-1 text-sm font-medium text-sky-700' : 'mt-1 text-sm font-medium bo-text--info' }}" role="status">Subiendo <span x-text="uploading"></span> archivo(s)…</p>
                            <p x-show="tooLarge.length" style="display: none" class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}" role="alert">No se agregaron porque superan {{ $maxUpload }}: <span x-text="tooLarge.join(', ')"></span>.</p>
                            @error('incomingFile') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror
                            @error('files') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror
                            @error('files.*') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror

                            @if ($files)
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                                    <p class="{{ $area === 'frontoffice' ? 'text-sm font-semibold text-slate-800' : 'text-sm font-semibold bo-strong' }}">Orden ({{ count($files) }} archivos{{ $tool->acceptsPdf() ? ' · '.array_sum($filePages).' páginas' : '' }})</p>
                                    <button type="button" wire:click="sortFilesByName" class="{{ $area === 'frontoffice' ? 'text-sm font-semibold text-sky-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700' : 'text-sm font-semibold hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 bo-text--info' }}">Ordenar por nombre</button>
                                </div>
                                <ol class="mt-2 grid gap-2">
                                    @foreach ($files as $index => $listedFile)
                                        <li wire:key="listed-file-{{ $index }}-{{ $listedFile->getFilename() }}" class="{{ $area === 'frontoffice' ? 'flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2' : 'flex items-center gap-3 rounded-lg border px-3 py-2 bo-panel-subtle' }}">
                                            <span class="{{ $area === 'frontoffice' ? 'w-6 shrink-0 text-center text-sm font-semibold text-slate-500' : 'w-6 shrink-0 text-center text-sm font-semibold bo-muted' }}">{{ $index + 1 }}</span>
                                            <i class="fa-solid {{ $tool->acceptsPdf() ? 'fa-file-pdf' : 'fa-file-image' }} text-sky-700" aria-hidden="true"></i>
                                            <span class="min-w-0 flex-1">
                                                <span class="{{ $area === 'frontoffice' ? 'block break-all text-sm font-medium text-slate-800' : 'block break-all text-sm font-medium bo-strong' }}">{{ $listedFile->getClientOriginalName() }}</span>
                                                <span class="{{ $area === 'frontoffice' ? 'block text-xs text-slate-500' : 'block text-xs bo-muted' }}">@if ($filePages[$index] !== null){{ $filePages[$index] }} {{ $filePages[$index] === 1 ? 'página' : 'páginas' }} · @endif{{ Illuminate\Support\Number::fileSize($listedFile->getSize(), maxPrecision: 1) }}</span>
                                            </span>
                                            <span class="flex shrink-0 gap-1">
                                                <button type="button" wire:click="moveFile({{ $index }}, -1)" @disabled($index === 0) aria-label="Subir {{ $listedFile->getClientOriginalName() }}" class="{{ $area === 'frontoffice' ? 'rounded-md p-2 text-slate-600 hover:bg-white hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30' : 'rounded-md p-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30 bo-panel-subtle bo-muted bo-text--info' }}"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                                <button type="button" wire:click="moveFile({{ $index }}, 1)" @disabled($loop->last) aria-label="Bajar {{ $listedFile->getClientOriginalName() }}" class="{{ $area === 'frontoffice' ? 'rounded-md p-2 text-slate-600 hover:bg-white hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30' : 'rounded-md p-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30 bo-panel-subtle bo-muted bo-text--info' }}"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                                <button type="button" wire:click="removeFile({{ $index }})" aria-label="Quitar {{ $listedFile->getClientOriginalName() }}" class="{{ $area === 'frontoffice' ? 'rounded-md p-2 text-slate-600 hover:bg-white hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700' : 'rounded-md p-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700 bo-panel-subtle bo-muted bo-text--danger' }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    @elseif ($format !== 'audiocut')
                        {{-- Checked in the capture phase so an oversized file never starts an upload the server would drop. --}}
                        <div wire:key="upload-single-{{ $format }}" x-data="{ tooLarge: false }" x-on:change.capture="tooLarge = ($event.target.files[0]?.size ?? 0) > {{ $maxUploadBytes }}; if (tooLarge) { $event.stopPropagation(); $event.target.value = ''; }">
                            <label for="conversion-file" class="{{ $area === 'frontoffice' ? 'mb-1.5 block text-sm font-semibold text-slate-800' : 'mb-1.5 block text-sm font-semibold bo-strong' }}">Archivo {{ $types }}</label>
                            <input id="conversion-file" type="file" wire:model="file" accept="{{ $accept }}" class="{{ $area === 'frontoffice' ? 'block w-full rounded-lg border border-dashed border-sky-300 bg-sky-50/60 p-4 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0A2E5D] file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700' : 'bo-input block w-full text-sm' }}">
                            <p class="{{ $area === 'frontoffice' ? 'mt-2 text-xs text-slate-600' : 'mt-2 text-xs bo-muted' }}">{{ match ($format) {
                                'pdf' => 'PDF con texto seleccionable · máximo '.$maxUpload.'. El Word reconstruye párrafos, tablas, encabezados y pies; revísalo antes de usarlo.',
                                'm4a' => 'Audio M4A · máximo '.$maxUpload.'.',
                                'word2pdf' => $types.' · máximo '.$maxUpload.'. Si el servidor no tiene las fuentes del documento, la paginación puede cambiar.',
                                'ocr' => 'PDF escaneado de hasta '.number_format(config('conversions.ocr_max_pages'), 0, ',', '.').' páginas · máximo '.$maxUpload.'. Un PDF grande puede tardar varios minutos.',
                                default => 'Un PDF · máximo '.$maxUpload.'.',
                            } }}</p>
                            <p x-show="tooLarge" style="display: none" class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}" role="alert">El archivo supera el máximo de {{ $maxUpload }} que acepta el servidor. Reduce su tamaño (por ejemplo, dividiéndolo o comprimiéndolo) e inténtalo de nuevo.</p>
                            @if ($pageCount)
                                <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-slate-700' : 'mt-1 text-sm bo-strong' }}">El PDF tiene {{ $pageCount }} {{ $pageCount === 1 ? 'página' : 'páginas' }}.</p>
                            @endif
                            @error('file') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror
                            @error('format') <p class="{{ $area === 'frontoffice' ? 'mt-1 text-sm text-red-700' : 'mt-1 text-sm bo-text--danger' }}">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @includeIf('livewire.partials.conversion-tools.'.$format)

                    @if ($format !== 'audiocut')
                    <div class="{{ $area === 'frontoffice' ? 'flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5' : 'flex flex-wrap items-center justify-between gap-3 border-t pt-5' }}">
                        <p class="{{ $area === 'frontoffice' ? 'text-sm text-slate-600' : 'text-sm bo-muted' }}">{{ $area === 'frontoffice' ? 'Te quedan '.($remainingByFormat[$format] ?? 0).' usos de esta herramienta en esta sesión.' : 'Sin límite de uso para tu cuenta.' }}</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="convert,file,incomingFile" @disabled($area === 'frontoffice' && ($remainingByFormat[$format] ?? 0) === 0) class="rounded-lg {{ $area === 'frontoffice' ? 'bg-blue-700 hover:bg-blue-800' : 'bo-btn bo-btn--primary' }} px-6 py-3 text-sm font-semibold text-white transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="convert,file,incomingFile">{{ ['merge' => 'Unir PDF', 'split' => 'Dividir PDF', 'rotate' => 'Rotar PDF', 'organize' => 'Guardar PDF organizado', 'compress' => 'Comprimir PDF', 'ocr' => 'Reconocer texto', 'pagenum' => 'Agregar números', 'word2pdf' => 'Convertir a PDF', 'img2pdf' => 'Convertir a PDF'][$format] ?? 'Convertir archivo' }}</span>
                            <span wire:loading wire:target="convert,file,incomingFile">Subiendo archivo…</span>
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        </section>

        <section class="{{ $area === 'frontoffice' ? 'bg-white p-5 fo-panel' : 'bo-card bo-section p-6' }}" aria-labelledby="jobs-title">
            @error('file') <p role="alert" class="{{ $area === 'frontoffice' ? 'fo-hint fo-hint--warn' : 'text-sm font-medium bo-text--danger' }}">{{ $message }}</p> @enderror
            @if ($area === 'frontoffice')
            <h2 id="jobs-title" class="{{ $area === 'frontoffice' ? 'text-lg font-semibold text-slate-900' : 'text-lg font-semibold bo-strong' }}">Archivos de {{ $area === 'frontoffice' ? 'esta sesión' : 'tu cuenta' }}</h2>
            @else
                <h3 id="jobs-title" class="text-lg font-semibold bo-strong">Archivos de tu cuenta</h3>
            @endif
            @if ($area === 'frontoffice')
                <div class="fo-table-wrap fo-table-wrap--glass fo-conv-table mt-4" aria-live="polite">
                    <table class="fo-table">
                        <caption class="sr-only">Archivos de esta sesión</caption>
                        <thead><tr><th scope="col">Archivo</th><th scope="col">Herramienta</th><th scope="col">Estado</th><th scope="col">Fecha</th><th scope="col">Acción</th></tr></thead>
                        @forelse ($jobs as $job)
                            <tbody class="fo-table-group" wire:key="conversion-{{ $job->id }}"><tr>
                                <td data-label="Archivo"><strong>{{ $job->original_name }}</strong>@if ($job->status === 'ready' && $job->notice)<p class="fo-hint">{{ $job->notice }}</p>@endif</td>
                                <td data-label="Herramienta">{{ config('conversions.tools.'.$job->format.'.label', strtoupper($job->format)) }}</td>
                                <td data-label="Estado">@if ($job->status === 'failed')<span class="fo-badge">{{ $job->error_message }}</span>@elseif ($job->status === 'ready' && ! $job->expires_at?->isFuture())<span class="fo-badge">Descarga vencida</span>@elseif ($job->status === 'ready')<span class="fo-badge">Listo</span>@else<span class="fo-conv-stage" aria-live="polite">@include('livewire.partials.conversion-status', ['prefix' => 'fo'])</span>@endif</td>
                                <td data-label="Fecha"><span class="fo-num">{{ $job->created_at->format('d/m/Y H:i') }}</span></td>
                                <td data-label="Acción">@if ($job->status === 'ready' && $job->expires_at?->isFuture())<a class="fo-link" href="{{ route('conversions.download', $job) }}">Descargar {{ App\Conversions\ConversionTool::downloadLabel(pathinfo($job->result_name, PATHINFO_EXTENSION)) }}</a>@endif @if (($job->format === 'pdf' && in_array($job->status, ['ready', 'failed'])) || ($job->format === 'ocr' && $job->status === 'ready')) <button type="button" class="fo-link" wire:click="transcribeWithOcr('{{ $job->token }}')" wire:loading.attr="disabled">Transcribir con OCR</button> @endif</td>
                            </tr></tbody>
                        @empty
                            <tbody><tr><td colspan="5" class="fo-table-empty">Elige una herramienta y un archivo para empezar.</td></tr></tbody>
                        @endforelse
                    </table>
                </div>
            @else
            <div class="mt-4 grid gap-3" aria-live="polite">
                @forelse ($jobs as $job)
                    <div wire:key="conversion-{{ $job->id }}" class="{{ $area === 'frontoffice' ? 'flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3' : 'flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3 bo-panel-subtle' }}">
                        <div class="min-w-0">
                            <p class="{{ $area === 'frontoffice' ? 'break-all text-sm font-semibold text-slate-800' : 'break-all text-sm font-semibold bo-strong' }}">{{ $job->original_name }}</p>
                            <p class="{{ $area === 'frontoffice' ? 'mt-1 text-xs text-slate-500' : 'mt-1 text-xs bo-muted' }}">{{ config('conversions.tools.'.$job->format.'.label', strtoupper($job->format)) }} · {{ $job->created_at->format('d/m/Y H:i') }}</p>
                            @if ($job->status === 'ready' && $job->notice)
                                <p class="mt-1 text-xs font-medium text-amber-800">{{ $job->notice }}</p>
                            @endif
                        </div>
                        @if ($job->status === 'ready' && $job->expires_at?->isFuture())
                            <a href="{{ route('conversions.download', $job) }}" class="rounded-lg {{ $area === 'frontoffice' ? 'bg-blue-700 hover:bg-blue-800' : 'bg-sky-700 hover:bg-sky-800' }} px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Descargar {{ App\Conversions\ConversionTool::downloadLabel(pathinfo($job->result_name, PATHINFO_EXTENSION)) }}</a>
                        @elseif ($job->status === 'failed')
                            <span class="{{ $area === 'frontoffice' ? 'text-sm text-red-700' : 'text-sm bo-text--danger' }}">{{ $job->error_message }}</span>
                        @elseif ($job->status === 'ready')
                            <span class="{{ $area === 'frontoffice' ? 'text-sm text-slate-600' : 'text-sm bo-muted' }}">Descarga vencida</span>
                        @else
                            <span class="{{ $area === 'frontoffice' ? 'text-sm font-medium text-sky-700' : 'text-sm font-medium bo-text--info' }}">@include('livewire.partials.conversion-status', ['prefix' => $area === 'frontoffice' ? 'fo' : 'bo'])</span>
                        @endif
                        @if (($job->format === 'pdf' && in_array($job->status, ['ready', 'failed'])) || ($job->format === 'ocr' && $job->status === 'ready'))
                            <button type="button" class="bo-btn bo-btn--secondary" wire:click="transcribeWithOcr('{{ $job->token }}')" wire:loading.attr="disabled">Transcribir con OCR</button>
                        @endif
                    </div>
                @empty
                    <p class="{{ $area === 'frontoffice' ? 'rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-600' : 'rounded-xl border border-dashed p-6 text-sm bo-muted' }}">Elige una herramienta y un archivo para empezar.</p>
                @endforelse
            </div>
            @endif
        </section>
    </div>

    @if ($format === 'audiocut')
        @include('livewire.partials.audio-cutter-modal')
    @endif
</div>
