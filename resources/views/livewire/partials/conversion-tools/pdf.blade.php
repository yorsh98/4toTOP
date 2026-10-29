@if (app(\App\Services\PdfOcr::class)->enabled())
    <label class="block text-sm"><input type="checkbox" wire:change="$set('options.ocr', $event.target.checked ? 'force' : null)" @checked(($options['ocr'] ?? null) === 'force')> Es un documento escaneado o con fotos: transcribir con OCR</label>
    @error('options.ocr') <p role="alert">{{ $message }}</p> @enderror
@endif
