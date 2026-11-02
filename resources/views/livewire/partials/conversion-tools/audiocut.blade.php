<div class="ac-intro flex flex-col gap-4 rounded-xl {{ $area === 'frontoffice' ? 'border' : 'ac-intro-border' }} border-slate-200 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex min-w-0 gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#0A2E5D] text-white"><i class="fa-solid fa-scissors" aria-hidden="true"></i></span>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900">Corta un trozo de una grabación en 4 pasos</p>
            <p class="mt-1 text-sm text-slate-600">Sube el audio, marca los fragmentos, elige qué hacer con ellos y descarga el resultado. Si cierras el cortador, tu avance se conserva mientras no recargues la página ni cambies de herramienta.</p>
        </div>
    </div>
    <button type="button" x-on:click="$dispatch('open-audio-cutter')" class="cm-press inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0A2E5D] px-5 text-sm font-semibold text-white hover:bg-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>Abrir el cortador
    </button>
</div>
