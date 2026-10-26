@if ($pageCount)
    <fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
        <legend class="px-1 text-sm font-semibold text-slate-800">Numeración</legend>
        <div>
            <p class="mb-1.5 text-sm font-semibold text-slate-800">Posición</p>
            <div class="grid w-full max-w-[12rem] grid-cols-3 gap-x-2 gap-y-16 rounded-md border border-slate-300 bg-white p-2 shadow-sm" role="radiogroup" aria-label="Posición del número">
                @foreach (App\Conversions\PageNumbersTool::POSITIONS as $position => $label)
                    <label class="flex h-10 cursor-pointer items-center justify-center rounded-md border-2 text-xs transition has-[:checked]:border-sky-700 has-[:checked]:bg-sky-50 border-slate-200 hover:border-sky-300" title="{{ $label }}">
                        <input type="radio" wire:model="options.position" value="{{ $position }}" class="sr-only">
                        <span aria-hidden="true">#</span><span class="sr-only">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="num-format" class="mb-1.5 block text-sm font-semibold text-slate-800">Formato</label>
                <select id="num-format" wire:model="options.format" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                    @foreach (App\Conversions\PageNumbersTool::FORMATS as $key => $pattern)
                        <option value="{{ $key }}">{{ str_replace(['{n}', '{total}'], ['1', $pageCount], $pattern) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="num-margin" class="mb-1.5 block text-sm font-semibold text-slate-800">Margen</label>
                <select id="num-margin" wire:model="options.margin" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                    @foreach (App\Conversions\PageNumbersTool::MARGINS as $margin => $label)
                        <option value="{{ $margin }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="num-size" class="mb-1.5 block text-sm font-semibold text-slate-800">Tamaño de letra</label>
                <input id="num-size" type="number" min="6" max="36" wire:model="options.size" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.size') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="num-first" class="mb-1.5 block text-sm font-semibold text-slate-800">Desde la página</label>
                <input id="num-first" type="number" min="1" max="{{ $pageCount }}" wire:model="options.first" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.first') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="num-last" class="mb-1.5 block text-sm font-semibold text-slate-800">Hasta la página</label>
                <input id="num-last" type="number" min="1" max="{{ $pageCount }}" wire:model="options.last" placeholder="{{ $pageCount }}" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.last') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="num-start" class="mb-1.5 block text-sm font-semibold text-slate-800">Primer número</label>
                <input id="num-start" type="number" min="0" wire:model="options.start" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.start') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-slate-600">«Fs.» sirve para foliar expedientes. Para no numerar la carátula, empieza desde la página 2.</p>
    </fieldset>
@endif
