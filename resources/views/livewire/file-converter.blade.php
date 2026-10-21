@if ($area === 'backoffice')
    <x-slot:header>
        <x-page-heading eyebrow="Herramientas · Backoffice" title="Convertir archivos">
            <x-slot:actions>
                @can('conversiones.ver_registro')
                    <a href="{{ route('conversions.history') }}" class="text-sm font-semibold text-sky-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700">Ver registro</a>
                @endcan
            </x-slot:actions>
        </x-page-heading>
    </x-slot:header>
@endif

<div class="{{ $area === 'frontoffice' ? 'pb-6' : 'py-6 sm:py-8' }}" wire:poll.5s>
    <div class="mx-auto space-y-6 {{ $area === 'frontoffice' ? 'w-full lg:space-y-8' : 'max-w-5xl px-4 sm:px-6 lg:px-8' }}">
        @if ($area === 'frontoffice')
            <div class="relative grid gap-6 pt-5 animate__animated animate__fadeInLeft motion-reduce:animate-none lg:gap-8">
                <a href="{{ url('/') }}" class="absolute left-4 top-4 z-10 inline-flex items-center gap-1 rounded-md bg-white px-3 py-1 text-sm font-semibold text-black shadow-md transition hover:bg-blue-700 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7" /></svg>
                    INICIO
                </a>
                <div class="flex items-start justify-center gap-4 rounded-lg bg-white px-6 pb-6 pt-12 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] sm:p-6 lg:pb-10">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16"><i class="fa-solid fa-arrow-right-arrow-left text-2xl text-[#0A2E5D]" aria-hidden="true"></i></span>
                    <div class="min-w-0 pt-3 sm:pt-5">
                        <h1 class="text-xl font-semibold text-black">CONVERTIR ARCHIVOS</h1>
                    </div>
                </div>
            </div>
        @endif

        <section class="overflow-hidden bg-white {{ $area === 'frontoffice' ? 'rounded-lg p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] animate__animated animate__fadeInUp motion-reduce:animate-none lg:p-10' : 'rounded-2xl border border-slate-200 shadow-lg shadow-blue-950/10' }}" aria-labelledby="conversion-title">
            <div class="{{ $area === 'frontoffice' ? 'border-b border-slate-200 pb-5 text-slate-900' : 'border-b border-slate-200 bg-[#0A2E5D] px-6 py-6 text-white sm:px-8' }}">
                <p class="text-xs font-semibold uppercase tracking-wide {{ $area === 'frontoffice' ? 'text-[#0A2E5D]' : 'font-mono text-sky-200' }}">PDF a Word · M4A a MP3</p>
                <h2 id="conversion-title" class="mt-2 text-xl font-semibold">Elige qué archivo quieres convertir</h2>
                <p class="mt-1 text-sm {{ $area === 'frontoffice' ? 'text-slate-600' : 'text-blue-100' }}">Tus archivos se eliminan automáticamente después del plazo de descarga.</p>
            </div>

            <div class="space-y-7 {{ $area === 'frontoffice' ? 'pt-6' : 'p-5 sm:p-8' }}">
                <div class="grid gap-3 sm:grid-cols-2" role="group" aria-label="Tipo de conversión">
                    @foreach (['pdf' => ['PDF', 'Word', 'fa-file-pdf'], 'm4a' => ['M4A', 'MP3', 'fa-file-audio']] as $option => $labels)
                        <button type="button" wire:click="selectFormat('{{ $option }}')" aria-pressed="{{ $format === $option ? 'true' : 'false' }}" class="flex items-center justify-between gap-3 rounded-xl border-2 p-4 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 {{ $format === $option ? 'border-sky-700 bg-sky-50 text-[#0A2E5D]' : 'border-slate-200 bg-white text-slate-700 hover:border-sky-300' }}">
                            <span class="flex items-center gap-3"><i class="fa-solid {{ $labels[2] }} text-xl text-sky-700" aria-hidden="true"></i><span class="font-semibold">{{ $labels[0] }} <i class="fa-solid fa-arrow-right mx-1 text-xs text-slate-400" aria-hidden="true"></i> {{ $labels[1] }}</span></span>
                            @if ($area === 'frontoffice')<span class="text-xs font-medium text-slate-500">{{ $remainingByFormat[$option] }} de 2</span>@endif
                        </button>
                    @endforeach
                </div>

                <form wire:submit="convert" class="grid gap-5">
                    @if ($area === 'frontoffice')
                        <div>
                            <label for="actor-name" class="mb-1.5 block text-sm font-semibold text-slate-800">Nombre de quien realiza la conversión</label>
                            <input id="actor-name" type="text" wire:model="actorName" required maxlength="120" autocomplete="name" placeholder="Escribe tu nombre" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                            @error('actorName') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @php($maxUpload = Illuminate\Support\Number::fileSize($maxUploadBytes, maxPrecision: 1))
                    {{-- Checked in the capture phase so an oversized file never starts an upload the server would drop. --}}
                    <div x-data="{ tooLarge: false }" x-on:change.capture="tooLarge = ($event.target.files[0]?.size ?? 0) > {{ $maxUploadBytes }}; if (tooLarge) { $event.stopPropagation(); $event.target.value = ''; }">
                        <label for="conversion-file" class="mb-1.5 block text-sm font-semibold text-slate-800">Archivo {{ strtoupper($format) }}</label>
                        <input id="conversion-file" type="file" wire:model="file" accept="{{ $format === 'pdf' ? '.pdf,application/pdf' : '.m4a,audio/mp4,audio/x-m4a' }}" class="block w-full rounded-lg border border-dashed border-sky-300 bg-sky-50/60 p-4 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0A2E5D] file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700">
                        <p class="mt-2 text-xs text-slate-600">{{ $format === 'pdf' ? 'PDF con texto seleccionable · máximo '.$maxUpload.'. El Word reconstruye párrafos, tablas, encabezados y pies; revísalo antes de usarlo.' : 'Audio M4A · máximo '.$maxUpload.'.' }}</p>
                        <p x-show="tooLarge" style="display: none" class="mt-1 text-sm text-red-700" role="alert">El archivo supera el máximo de {{ $maxUpload }} que acepta el servidor. Reduce su tamaño (por ejemplo, dividiéndolo o comprimiéndolo) e inténtalo de nuevo.</p>
                        @error('file') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        @error('format') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                        <p class="text-sm text-slate-600">{{ $area === 'frontoffice' ? 'Te quedan '.($remainingByFormat[$format] ?? 0).' conversiones de este formato en esta sesión.' : 'Sin límite de conversiones para tu cuenta.' }}</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="convert,file" @disabled($area === 'frontoffice' && ($remainingByFormat[$format] ?? 0) === 0) class="rounded-lg {{ $area === 'frontoffice' ? 'bg-blue-700 hover:bg-blue-800' : 'bg-[#0A2E5D] hover:bg-sky-800' }} px-6 py-3 text-sm font-semibold text-white transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="convert,file">Convertir archivo</span>
                            <span wire:loading wire:target="convert,file">Subiendo archivo…</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="bg-white p-5 {{ $area === 'frontoffice' ? 'rounded-lg shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] animate__animated animate__fadeInUp motion-reduce:animate-none sm:p-6 lg:p-10' : 'rounded-2xl border border-slate-200 shadow-sm sm:p-8' }}" aria-labelledby="jobs-title">
            <h2 id="jobs-title" class="text-lg font-semibold text-slate-900">Conversiones de {{ $area === 'frontoffice' ? 'esta sesión' : 'tu cuenta' }}</h2>
            <div class="mt-4 grid gap-3" aria-live="polite">
                @forelse ($jobs as $job)
                    <div wire:key="conversion-{{ $job->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="min-w-0">
                            <p class="break-all text-sm font-semibold text-slate-800">{{ $job->original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ strtoupper($job->format) }} → {{ $job->format === 'pdf' ? 'DOCX' : 'MP3' }} · {{ $job->created_at->format('d/m/Y H:i') }}</p>
                            @if ($job->status === 'ready' && $job->notice)
                                <p class="mt-1 text-xs font-medium text-amber-800">{{ $job->notice }}</p>
                            @endif
                        </div>
                        @if ($job->status === 'ready' && $job->expires_at?->isFuture())
                            <a href="{{ route('conversions.download', $job) }}" class="rounded-lg {{ $area === 'frontoffice' ? 'bg-blue-700 hover:bg-blue-800' : 'bg-sky-700 hover:bg-sky-800' }} px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Descargar {{ $job->format === 'pdf' ? 'Word' : 'MP3' }}</a>
                        @elseif ($job->status === 'failed')
                            <span class="text-sm text-red-700">{{ $job->error_message }}</span>
                        @elseif ($job->status === 'ready')
                            <span class="text-sm text-slate-600">Descarga vencida</span>
                        @else
                            <span class="text-sm font-medium text-sky-700">{{ $job->status === 'queued' ? 'En espera' : 'Convirtiendo…' }}</span>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-600">Elige un formato y un archivo para hacer tu primera conversión.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
