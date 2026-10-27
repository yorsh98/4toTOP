<fieldset class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:p-5">
    <legend class="px-1 text-sm font-semibold text-slate-800">Nivel de compresión</legend>
    <div class="grid gap-2 sm:grid-cols-3">
        @foreach (['extreme' => ['Extrema', 'Menor tamaño, menor calidad (72 ppp).'], 'recommended' => ['Recomendada', 'Buena calidad y buen tamaño (150 ppp).'], 'low' => ['Baja', 'Alta calidad, menos compresión (300 ppp).']] as $level => [$label, $description])
            <label class="flex cursor-pointer gap-3 rounded-lg border-2 p-3 transition border-slate-200 hover:border-sky-300 has-[:checked]:border-sky-700 has-[:checked]:bg-sky-50">
                <input type="radio" wire:model.live="options.level" value="{{ $level }}" class="mt-0.5 border-slate-300 text-sky-700 focus:ring-sky-700">
                <span><span class="block text-sm font-semibold text-slate-800">{{ $label }}</span><span class="block text-xs text-slate-600">{{ $description }}</span></span>
            </label>
        @endforeach
    </div>
    @error('options.level') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
</fieldset>
