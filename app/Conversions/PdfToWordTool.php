<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\LibreOffice;
use App\Services\LibreOfficeDocxNormalizer;
use App\Services\PdfInspector;
use App\Services\PdfOcr;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PdfToWordTool extends ConversionTool
{
    public function resultExtension(): string
    {
        return 'docx';
    }

    public function defaults(): array
    {
        return ['ocr' => null];
    }

    public function rules(): array
    {
        return ['ocr' => ['nullable', 'in:force']];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        return ($options['ocr'] ?? null) === 'force' ? ['ocr' => 'force'] : [];
    }

    public function timeout(array $options): int
    {
        return app(PdfOcr::class)->enabled() ? OcrPdfTool::ocrTimeout() : parent::timeout($options);
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $deadline = microtime(true) + $this->timeout($conversion->options ?? []) - 30;
        $input = $directory.'/'.basename($conversion->input_path);
        $output = $directory.'/result.docx';
        $this->engine = config('conversions.pdf_engine');
        $this->stage('Analizando el PDF', 5);
        $pdf = app(PdfInspector::class)->inspect($input);
        $scanned = $pdf['pages_without_text'];
        $ocrPath = $directory.'/ocr.pdf';
        $converted = $directory.'/converted.docx';
        $notice = null;

        try {
            if (app(PdfOcr::class)->enabled()) {
                $pages = $this->rebuild($input, $ocrPath, ($conversion->options['ocr'] ?? null) === 'force' ? 'all' : 'auto', $scanned, $deadline);
                if ($pages !== []) {
                    $input = $ocrPath;
                    $notice = 'Se transcribió con OCR el texto de '.self::pageList($pages).'. Revisa el texto reconocido antes de usarlo.';
                }
                $remaining = array_values(array_diff($scanned, $pages));
                if ($remaining !== []) {
                    $notice = trim(($notice ?? '').' No hay texto seleccionable en '.self::pageList($remaining).' (posiblemente escaneadas): se incluyen como imagen, sin texto editable.');
                }
            } elseif (count($scanned) === $pdf['pages']) {
                throw new \InvalidArgumentException('El PDF no contiene texto editable. Los PDF escaneados requieren OCR.');
            } elseif ($scanned !== []) {
                $notice = 'No hay texto seleccionable en '.self::pageList($scanned).' (posiblemente escaneadas): se incluyen como imagen, sin texto editable.';
            }

            // No silent fallback: a failure of the configured engine is reported as such.
            $this->stage('Armando el documento Word', 85);
            $this->engine = config('conversions.pdf_engine');
            match ($this->engine) {
                'pdf2docx' => $this->convertWithPdf2Docx($input, $converted, $deadline),
                'libreoffice' => $this->convertWithLibreOffice($input, $converted, $deadline),
                default => throw new \RuntimeException('Motor de conversión PDF desconocido: '.$this->engine),
            };

            $this->stage('Verificando el texto del Word', 95);
            $this->assertEditableText($converted);

            if (! rename($converted, $output)) {
                throw new \RuntimeException('No se pudo guardar el documento Word.');
            }
        } catch (ProcessTimedOutException $exception) {
            throw new \InvalidArgumentException('El documento es demasiado largo para convertirlo. Divídelo e inténtalo de nuevo.', previous: $exception);
        } finally {
            foreach ([$converted, $ocrPath] as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }

        return new ToolResult($output, $notice);
    }

    private function rebuild(string $input, string $output, string $pages, array $scanned, float $deadline): array
    {
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/rebuild_scanned_pdf.py'), $input, $output, '--tesseract', config('conversions.tesseract'), '--lang', config('conversions.ocr_languages'), '--pages', $pages, '--extra-pages', implode(',', $scanned), '--max-pages', (string) config('conversions.ocr_max_pages')]);
        $process->setTimeout(min(config('conversions.ocr_timeout'), $this->remaining($deadline)));
        $buffer = '';
        $summary = [];
        $process->run(function ($type, $chunk) use (&$buffer, &$summary) {
            if ($type !== Process::OUT) {
                return;
            }
            $buffer .= $chunk;
            while (($end = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $end);
                $buffer = substr($buffer, $end + 1);
                if (preg_match('/^PROGRESS (\d+) (\d+)$/', $line, $match)) {
                    $this->stage("Transcribiendo con OCR: página {$match[1]} de {$match[2]}", 10 + (int) (65 * ((int) $match[1] - 1) / max(1, (int) $match[2])));
                } elseif ($line === 'STAGE images') {
                    $this->stage('Separando firmas, timbres e imágenes');
                } elseif (str_starts_with($line, 'SUMMARY ')) {
                    $summary = json_decode(substr($line, 8), true) ?? [];
                }
            }
        });
        if (! $process->isSuccessful() || ! is_file($output)) {
            if ($process->getExitCode() === 3) {
                throw new \InvalidArgumentException('El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.');
            }
            throw new \RuntimeException('OCR: '.mb_substr($process->getErrorOutput(), -500));
        }

        return $summary['pages_rebuilt'] ?? [];
    }

    private function convertWithPdf2Docx(string $input, string $converted, float $deadline): void
    {
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/pdf_to_docx.py'), $input, $converted]);
        $process->setTimeout(min(300, $this->remaining($deadline)));
        $process->run();

        if (! $process->isSuccessful() || ! is_file($converted) || filesize($converted) === 0) {
            if ($process->getExitCode() === 3) {
                throw new \InvalidArgumentException('El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.');
            }
            throw new \RuntimeException('pdf2docx: '.mb_substr($process->getErrorOutput(), -500));
        }
    }

    private function convertWithLibreOffice(string $input, string $converted, float $deadline): void
    {
        $output = app(LibreOffice::class)->convert($input, dirname($converted), 'docx:Office Open XML Text', 'docx', ['--infilter=writer_pdf_import'], min(300, $this->remaining($deadline)));

        try {
            app(LibreOfficeDocxNormalizer::class)->normalize($output);

            if (! rename($output, $converted)) {
                throw new \RuntimeException('No se pudo guardar el documento Word.');
            }
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }
    }

    private function remaining(float $deadline): float
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new \InvalidArgumentException('El documento es demasiado largo para convertirlo. Divídelo e inténtalo de nuevo.');
        }

        return $remaining;
    }

    private function assertEditableText(string $docx): void
    {
        $archive = new \ZipArchive;
        if ($archive->open($docx) !== true) {
            throw new \RuntimeException('El motor no generó un documento Word válido.');
        }

        $xml = $archive->getFromName('word/document.xml');
        $archive->close();
        $word = new \DOMDocument;
        if ($xml === false || ! $word->loadXML($xml, LIBXML_NONET | LIBXML_PARSEHUGE)) {
            throw new \RuntimeException('El motor no generó un documento Word válido.');
        }

        foreach ($word->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $text) {
            if (trim($text->textContent) !== '') {
                return;
            }
        }

        throw new \InvalidArgumentException('El PDF no contiene texto editable. Los PDF escaneados requieren OCR.');
    }
}
