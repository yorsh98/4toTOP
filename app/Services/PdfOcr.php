<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/** Adds an invisible, selectable text layer with OCRmyPDF (Tesseract), keeping each page's original image. */
class PdfOcr
{
    // OCRmyPDF exit codes (ocrmypdf.ExitCode).
    private const ENCRYPTED_PDF = 8;

    private const PDFA_CONVERSION_FAILED = 10;

    public function enabled(): bool
    {
        return (bool) config('conversions.ocrmypdf');
    }

    /**
     * Pages that already have text are kept as they are, unless "redo" asks to replace a bad OCR layer.
     * Returns false when PDF/A was requested but OCRmyPDF could only produce a regular PDF.
     *
     * @param  array{language?: string, redo?: bool, deskew?: bool, pdfa?: bool}  $options
     */
    public function run(string $input, string $output, array $options = [], ?int $timeout = null): bool
    {
        $redo = $options['redo'] ?? false;
        $process = new Process([
            config('conversions.ocrmypdf'),
            $redo ? '--redo-ocr' : '--skip-text',
            // OCRmyPDF cannot deskew while redoing OCR; rotation by text orientation works in both modes.
            ...(($options['deskew'] ?? true) ? ($redo ? ['--rotate-pages'] : ['--rotate-pages', '--deskew']) : []),
            // A signature cannot stay valid once the file is rewritten; its appearance is kept.
            '--invalidate-digital-signatures',
            '--jobs', (string) max(1, config('conversions.ocr_jobs')),
            '-l', $options['language'] ?? config('conversions.ocr_languages'),
            '--output-type', ($options['pdfa'] ?? false) ? 'pdfa' : 'pdf',
            $input, $output,
        ]);
        $process->setTimeout($timeout ?? 300);
        $process->run();

        if ($process->getExitCode() === self::PDFA_CONVERSION_FAILED && is_file($output) && filesize($output) > 0) {
            return false;
        }
        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            if ($process->getExitCode() === self::ENCRYPTED_PDF) {
                throw new \InvalidArgumentException('El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.');
            }
            throw new \RuntimeException('OCRmyPDF: '.mb_substr($process->getErrorOutput(), -500));
        }

        return true;
    }
}
