<fieldset class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:p-5">
    <legend class="px-1 text-sm font-semibold text-slate-800">Opciones del PDF</legend>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="img-size" class="mb-1.5 block text-sm font-semibold text-slate-800">Tamaño de página</label>
            <select id="img-size" wire:model.live="options.page_size" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @foreach (App\Conversions\ImagesToPdfTool::PAGE_SIZES as $size => $label)
                    <option value="{{ $size }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="img-orientation" class="mb-1.5 block text-sm font-semibold text-slate-800">Orientación</label>
            <select id="img-orientation" wire:model="options.orientation" @disabled($options['page_size'] === 'fit') class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700 disabled:opacity-50">
                <option value="portrait">Vertical</option>
                <option value="landscape">Horizontal</option>
            </select>
        </div>
        <div>
            <label for="img-margin" class="mb-1.5 block text-sm font-semibold text-slate-800">Margen</label>
            <select id="img-margin" wire:model="options.margin" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-sky-700 focus:ring-sky-700">
                @foreach (App\Conversions\ImagesToPdfTool::MARGINS as $margin => $label)
                    <option value="{{ $margin }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid gap-2 text-sm text-slate-800">
        <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="options.merge" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Unir todas las imágenes en un solo PDF (si no, un PDF por imagen en un ZIP)</label>
        @if (app(App\Services\PdfOcr::class)->enabled())
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="options.ocr" class="rounded border-slate-300 text-sky-700 focus:ring-sky-700">Hacer el texto buscable (OCR)</label>
        @endif
    </div>
</fieldset>
