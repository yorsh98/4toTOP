@if ($pageCount)
    @php($extracting = str_starts_with($options['mode'], 'extract'))
    <fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
        <legend class="px-1 text-sm font-semibold text-slate-800">Cómo dividir</legend>
        <div class="grid gap-2 sm:grid-cols-2" role="group" aria-label="Modo de división">
            @foreach (['custom' => ['Dividir por rango', 'fa-arrows-left-right-to-line', ! $extracting], 'extract_all' => ['Extraer páginas', 'fa-file-export', $extracting]] as $mode => [$label, $icon, $active])
                <button type="button" wire:click="$set('options.mode', '{{ $mode }}')" aria-pressed="{{ $active ? 'true' : 'false' }}" class="flex items-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700 {{ $active ? 'border-sky-700 bg-sky-50 text-[#0A2E5D]' : 'border-slate-200 text-slate-700 hover:border-sky-300' }}"><i class="fa-solid {{ $icon }} text-sky-700" aria-hidden="true"></i>{{ $label }}</button>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-800">
            @foreach ($extracting ? ['extract_all' => 'Extraer todas las páginas', 'extract_select' => 'Seleccionar páginas'] : ['custom' => 'Rangos personalizados', 'fixed' => 'Rangos fijos'] as $mode => $label)
                <label class="inline-flex items-center gap-2"><input type="radio" wire:model.live="options.mode" value="{{ $mode }}" class="border-slate-300 text-sky-700 focus:ring-sky-700">{{ $label }}</label>
            @endforeach
        </div>

        @if ($options['mode'] === 'custom')
            <div>
                <label for="split-ranges" class="mb-1.5 block text-sm font-semibold text-slate-800">Rangos</label>
                <input id="split-ranges" type="text" wire:model="options.ranges" placeholder="1-3, 4-{{ $pageCount }}" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                <p class="mt-1 text-xs text-slate-600">Separa los rangos con comas. Cada rango será un PDF.</p>
                @error('options.ranges') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-800"><input type="checkbox" wire:model="options.merge" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Unir todos los rangos en un solo PDF</label>
        @elseif ($options['mode'] === 'fixed')
            <div>
                <label for="split-every" class="mb-1.5 block text-sm font-semibold text-slate-800">Dividir en archivos de</label>
                <div class="flex items-center gap-2">
                    <input id="split-every" type="number" min="1" max="{{ $pageCount }}" wire:model.live.debounce.300ms="options.every" class="w-28 rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                    <span class="text-sm text-slate-700">páginas</span>
                </div>
                @if ((int) $options['every'] >= 1)
                    <p class="mt-1 text-xs text-slate-600">Se crearán {{ (int) ceil($pageCount / (int) $options['every']) }} PDF.</p>
                @endif
                @error('options.every') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        @elseif ($options['mode'] === 'extract_all')
            <p class="text-sm text-slate-600">Cada página se guardará como un PDF independiente ({{ $pageCount }} archivos en un ZIP).</p>
        @else
            <div>
                <label for="split-pages" class="mb-1.5 block text-sm font-semibold text-slate-800">Páginas a extraer</label>
                <input id="split-pages" type="text" wire:model="options.pages" placeholder="1, 3, 5-7" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.pages') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-800"><input type="checkbox" wire:model="options.merge" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Unir las páginas extraídas en un solo PDF</label>
        @endif
        @error('options.mode') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
@endif
