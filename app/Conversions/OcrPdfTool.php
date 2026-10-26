<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfOcr;
use App\Services\PdfPageTools;
use Illuminate\Validation\ValidationException;

class OcrPdfTool extends ConversionTool
{
    public const LANGUAGES = ['spa' => 'Español', 'spa+eng' => 'Español e inglés'];

    public function enabled(): bool
    {
        return app(PdfOcr::class)->enabled();
    }

    public function needsPageCount(): bool
    {
        return true;
    }

    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function resultSuffix(): string
    {
        return '_ocr';
    }

    public function defaults(): array
    {
        return ['language' => 'spa', 'redo' => false, 'deskew' => true, 'pdfa' => false];
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'in:'.implode(',', array_keys(self::LANGUAGES))],
            'redo' => ['boolean'],
            'deskew' => ['boolean'],
            'pdfa' => ['boolean'],
        ];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        if ($pageCount > config('conversions.ocr_max_pages')) {
            throw ValidationException::withMessages(['file' => 'El PDF tiene '.$pageCount.' páginas; el OCR acepta hasta '.config('conversions.ocr_max_pages').'.']);
        }

        // Rejected here, before queueing, so a PDF that needs nothing does not use up the session quota.
        if (! $options['redo'] && app(PdfInspector::class)->inspect($files[0]->getRealPath())['pages_without_text'] === []) {
            throw ValidationException::withMessages(['file' => 'El PDF ya tiene texto seleccionable en todas sus páginas. Si el texto está mal reconocido, marca «Rehacer el OCR».']);
        }

        return ['language' => $options['language'], 'redo' => (bool) $options['redo'], 'deskew' => (bool) $options['deskew'], 'pdfa' => (bool) $options['pdfa']];
    }

    public function timeout(array $options): int
    {
        return self::ocrTimeout();
    }

    /** Queue timeout of a job that runs OCR: OCRmyPDF's own limit plus a margin for the steps around it. */
    public static function ocrTimeout(): int
    {
        return config('conversions.ocr_timeout') + 120;
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'ocrmypdf';
        $input = $directory.'/'.basename($conversion->input_path);
        $options = $conversion->options;
        $before = app(PdfInspector::class)->inspect($input)['pages_without_text'];
        $baked = $directory.'/baked.pdf';
        $output = $directory.'/result.pdf';

        try {
            // OCRmyPDF refuses signed PDFs; baking keeps the signature box visible, as merge and split do.
            $flattened = app(PdfPageTools::class)->bake($input, $baked);
            $pdfa = app(PdfOcr::class)->run($baked, $output, $options, config('conversions.ocr_timeout'));
        } finally {
            if (is_file($baked)) {
                unlink($baked);
            }
        }

        $after = app(PdfInspector::class)->inspect($output)['pages_without_text'];
        $recognized = array_values(array_diff($before, $after));
        $notices = [
            $options['redo'] ? 'Se rehízo el OCR de todas las páginas.' : ($recognized === [] ? null : 'Se reconoció texto en '.self::pageList($recognized).'.'),
            $after === [] ? null : 'No se encontró texto en '.self::pageList($after).' (en blanco o sin texto legible).',
            $options['pdfa'] && ! $pdfa ? 'No se pudo generar PDF/A; se entrega un PDF normal.' : null,
            self::flattenedNotice($flattened),
        ];

        return new ToolResult($output, implode(' ', array_filter($notices)) ?: null);
    }
}
