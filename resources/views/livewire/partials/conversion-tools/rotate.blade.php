@if ($pageCount)
    <fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
        <legend class="px-1 text-sm font-semibold text-slate-800">Rotación</legend>
        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Ángulo">
            @foreach ([90 => ['Derecha 90°', 'fa-rotate-right'], 180 => ['180°', 'fa-arrows-rotate'], 270 => ['Izquierda 90°', 'fa-rotate-left']] as $angle => [$label, $icon])
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-semibold transition has-[:checked]:border-sky-700 has-[:checked]:bg-sky-50 border-slate-200 hover:border-sky-300">
                    <input type="radio" wire:model="options.angle" value="{{ $angle }}" class="sr-only"><i class="fa-solid {{ $icon }} text-sky-700" aria-hidden="true"></i>{{ $label }}
                </label>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-800">
            <label class="inline-flex items-center gap-2"><input type="radio" wire:model.live="options.scope" value="all" class="border-slate-300 text-sky-700 focus:ring-sky-700">Todas las páginas</label>
            <label class="inline-flex items-center gap-2"><input type="radio" wire:model.live="options.scope" value="select" class="border-slate-300 text-sky-700 focus:ring-sky-700">Algunas páginas</label>
        </div>
        @if ($options['scope'] === 'select')
            <div>
                <label for="rotate-ranges" class="mb-1.5 block text-sm font-semibold text-slate-800">Páginas a rotar</label>
                <input id="rotate-ranges" type="text" wire:model="options.ranges" placeholder="1, 3, 5-7" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @error('options.ranges') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        @endif
    </fieldset>
@endif
