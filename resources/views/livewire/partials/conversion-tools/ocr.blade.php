<fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
    <legend class="px-1 text-sm font-semibold text-slate-800">Reconocimiento de texto</legend>
    <div>
        <label for="ocr-language" class="mb-1.5 block text-sm font-semibold text-slate-800">Idioma del documento</label>
        <select id="ocr-language" wire:model="options.language" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700 sm:w-72">
            @foreach (App\Conversions\OcrPdfTool::LANGUAGES as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('options.language') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
    <div class="grid gap-2 text-sm text-slate-800">
        <label class="inline-flex items-start gap-2"><input type="checkbox" wire:model="options.deskew" class="mt-0.5 rounded border-slate-300 text-sky-700 focus:ring-sky-700"><span>Enderezar páginas torcidas o giradas <span class="block text-xs text-slate-600">Mejora el reconocimiento; cambia levemente la imagen de esas páginas.</span></span></label>
        <label class="inline-flex items-start gap-2"><input type="checkbox" wire:model="options.redo" class="mt-0.5 rounded border-slate-300 text-sky-700 focus:ring-sky-700"><span>Rehacer el OCR <span class="block text-xs text-slate-600">Para escaneos que ya traen un texto mal reconocido. Sin esta opción, las páginas que ya tienen texto no se tocan.</span></span></label>
        <label class="inline-flex items-start gap-2"><input type="checkbox" wire:model="options.pdfa" class="mt-0.5 rounded border-slate-300 text-sky-700 focus:ring-sky-700"><span>Guardar como PDF/A <span class="block text-xs text-slate-600">Formato de archivo de largo plazo.</span></span></label>
    </div>
</fieldset>
