<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfOcr;
use App\Services\PdfPageTools;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImagesToPdfTool extends ConversionTool
{
    public const PAGE_SIZES = ['fit' => 'Ajustar a la imagen', 'oficio' => 'Oficio', 'letter' => 'Carta', 'a4' => 'A4'];

    public const MARGINS = ['none' => 'Sin margen', 'small' => 'Pequeño', 'large' => 'Grande'];

    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function defaults(): array
    {
        return ['page_size' => 'fit', 'orientation' => 'portrait', 'margin' => 'none', 'merge' => true, 'ocr' => false];
    }

    public function rules(): array
    {
        return [
            'page_size' => ['required', 'in:'.implode(',', array_keys(self::PAGE_SIZES))],
            'orientation' => ['required', 'in:portrait,landscape'],
            'margin' => ['required', 'in:'.implode(',', array_keys(self::MARGINS))],
            'merge' => ['boolean'],
            'ocr' => ['boolean'],
        ];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        return [
            'page_size' => $options['page_size'], 'orientation' => $options['orientation'], 'margin' => $options['margin'],
            'merge' => (bool) $options['merge'], 'ocr' => $options['ocr'] && app(PdfOcr::class)->enabled(),
        ];
    }

    public function timeout(array $options): int
    {
        return ($options['ocr'] ?? false) ? OcrPdfTool::ocrTimeout() : parent::timeout($options);
    }

    /** One PDF with every image, or one per image in a ZIP; optionally made searchable with OCR. */
    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $disk = Storage::disk(config('conversions.disk'));
        $options = $conversion->options;
        $images = array_map(fn (array $input) => ['path' => $disk->path($input['path']), 'name' => pathinfo($input['name'], PATHINFO_FILENAME)], $options['inputs']);
        $workDirectory = $directory.'/images';
        if (! is_dir($workDirectory) && ! mkdir($workDirectory, 0700, true)) {
            throw new \RuntimeException('No se pudo preparar la conversión de imágenes.');
        }

        $groups = $options['merge'] ? [$images] : array_map(fn (array $image) => [$image], $images);
        $parts = [];
        foreach ($groups as $index => $group) {
            $name = ($options['merge'] ? pathinfo($conversion->result_name, PATHINFO_FILENAME) : sprintf('%02d_%s', $index + 1, $group[0]['name'])).'.pdf';
            $parts[$name] = $workDirectory.'/'.$name;
        }

        try {
            app(PdfPageTools::class)->images([
                'parts' => array_map(fn (string $path, array $group) => ['path' => $path, 'images' => array_column($group, 'path')], array_values($parts), $groups),
                'page_size' => $options['page_size'], 'orientation' => $options['orientation'], 'margin' => $options['margin'],
            ]);

            if ($options['ocr']) {
                $this->engine = 'pymupdf+ocrmypdf';
                foreach ($parts as $path) {
                    app(PdfOcr::class)->run($path, $path.'.ocr', [], config('conversions.ocr_timeout'));
                    rename($path.'.ocr', $path);
                }
            }

            return new ToolResult(SplitPdfTool::deliver($parts, $directory), $options['ocr'] ? 'Se agregó texto buscable (OCR); revísalo antes de copiarlo.' : null);
        } finally {
            File::deleteDirectory($workDirectory);
            foreach ($images as $image) {
                File::delete(glob($image['path'].'.upright.*'));
            }
        }
    }
}
