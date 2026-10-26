<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\LibreOffice;
use App\Services\LibreOfficeDocxNormalizer;
use App\Services\PdfInspector;
use App\Services\PdfOcr;
use Symfony\Component\Process\Process;

class PdfToWordTool extends ConversionTool
{
    public function resultExtension(): string
    {
        return 'docx';
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $input = $directory.'/'.basename($conversion->input_path);
        $output = $directory.'/result.docx';
        $pdf = app(PdfInspector::class)->inspect($input);
        $scanned = $pdf['pages_without_text'];
        $ocrPath = $directory.'/ocr.pdf';
        $converted = $directory.'/converted.docx';
        $notice = null;

        try {
            if ($scanned !== [] && app(PdfOcr::class)->enabled()) {
                app(PdfOcr::class)->run($input, $ocrPath);
                $input = $ocrPath;
                $notice = 'Se reconoció texto (OCR) en '.self::pageList($scanned).'. Revisa el texto reconocido antes de usarlo.';
            } elseif (count($scanned) === $pdf['pages']) {
                throw new \InvalidArgumentException('El PDF no contiene texto editable. Los PDF escaneados requieren OCR.');
            } elseif ($scanned !== []) {
                $notice = 'No hay texto seleccionable en '.self::pageList($scanned).' (posiblemente escaneadas): se incluyen como imagen, sin texto editable.';
            }

            // No silent fallback: a failure of the configured engine is reported as such.
            $this->engine = config('conversions.pdf_engine');
            match ($this->engine) {
                'pdf2docx' => $this->convertWithPdf2Docx($input, $converted),
                'libreoffice' => $this->convertWithLibreOffice($input, $converted),
                default => throw new \RuntimeException('Motor de conversión PDF desconocido: '.$this->engine),
            };

            $this->assertEditableText($converted);

            if (! rename($converted, $output)) {
                throw new \RuntimeException('No se pudo guardar el documento Word.');
            }
        } finally {
            foreach ([$converted, $ocrPath] as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }

        return new ToolResult($output, $notice);
    }

    private function convertWithPdf2Docx(string $input, string $converted): void
    {
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/pdf_to_docx.py'), $input, $converted]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($converted) || filesize($converted) === 0) {
            if ($process->getExitCode() === 3) {
                throw new \InvalidArgumentException('El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.');
            }
            throw new \RuntimeException('pdf2docx: '.mb_substr($process->getErrorOutput(), -500));
        }
    }

    private function convertWithLibreOffice(string $input, string $converted): void
    {
        $output = app(LibreOffice::class)->convert($input, dirname($converted), 'docx:Office Open XML Text', 'docx', ['--infilter=writer_pdf_import']);

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
