<?php

namespace App\Console\Commands;

use App\Conversions\OcrPdfTool;
use App\Services\PdfOcr;
use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;

/** Checks, on the server, that every program the tools of /convertir-archivos need is installed and configured. */
class CheckConversionTools extends Command
{
    protected $signature = 'conversions:check';

    protected $description = 'Verify the programs and settings the file conversion tools need';

    private bool $failed = false;

    public function handle(): int
    {
        $python = config('conversions.python');
        $rows = [
            $this->check('Python (PyMuPDF, pdf2docx, Pillow)', 'Todas las herramientas PDF', [$python, '-I', '-c', 'import pymupdf, pdf2docx, docx, PIL; print("PyMuPDF", pymupdf.VersionBind)']),
            $this->check('Poppler pdfinfo', 'Todas las herramientas PDF', [config('conversions.pdfinfo'), '-v']),
            $this->check('Poppler pdftotext', 'PDF a Word, OCR', [config('conversions.pdftotext'), '-v']),
            $this->check('Poppler pdfimages', 'PDF a Word', [config('conversions.pdfimages'), '-v']),
            $this->check('Ghostscript', 'Comprimir, OCR', [config('conversions.ghostscript'), '--version']),
            $this->check('LibreOffice', 'Word a PDF', [config('conversions.libreoffice'), '--version']),
            $this->check('FFmpeg', 'M4A a MP3', [config('conversions.ffmpeg'), '-version']),
            $this->ocr(),
            $this->queue(),
            $this->uploads(),
        ];

        $this->table(['Componente', 'Estado', 'Usado por', 'Detalle'], $rows);

        if ($this->failed) {
            $this->error('Hay componentes con problemas. Ver docs/despliegue-herramientas-pdf.md.');

            return self::FAILURE;
        }
        $this->info('Todo listo.');

        return self::SUCCESS;
    }

    /** @param list<string|null> $command */
    private function check(string $name, string $usedBy, array $command): array
    {
        if (blank($command[0])) {
            return $this->row($name, false, $usedBy, 'No configurado.');
        }

        $process = new Process($command);
        $process->setTimeout(60);
        try {
            $process->run();
        } catch (\Throwable $exception) {
            return $this->row($name, false, $usedBy, $exception->getMessage());
        }
        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return $this->row($name, $process->isSuccessful(), $usedBy, mb_substr(strtok($output, "\n") ?: $output, 0, 70));
    }

    /** Runs OCR on a generated scan: proves OCRmyPDF, Tesseract with Spanish and orientation data, and Ghostscript. */
    private function ocr(): array
    {
        if (! app(PdfOcr::class)->enabled()) {
            return $this->row('OCR (OCRmyPDF + Tesseract spa)', false, 'OCR, Imagen a PDF', 'CONVERSIONS_OCRMYPDF vacío: la herramienta OCR queda oculta.');
        }

        $directory = sys_get_temp_dir().'/conversions-check-'.bin2hex(random_bytes(4));
        mkdir($directory, 0700);
        try {
            $scan = new Process([config('conversions.python'), '-I', '-c', <<<'PY'
import sys, pymupdf
source = pymupdf.open()
source.new_page().insert_text((72, 120), 'Tribunal de garantia', fontsize=36)
scan = pymupdf.open()
page = scan.new_page()
page.insert_image(page.rect, pixmap=source[0].get_pixmap(dpi=200))
scan.save(sys.argv[1])
PY, $directory.'/scan.pdf']);
            $scan->mustRun();
            app(PdfOcr::class)->run($directory.'/scan.pdf', $directory.'/ocr.pdf', ['language' => 'spa'], 120);
            $text = new Process([config('conversions.pdftotext'), $directory.'/ocr.pdf', '-']);
            $text->mustRun();

            return $this->row('OCR (OCRmyPDF + Tesseract spa)', str_contains($text->getOutput(), 'Tribunal'), 'OCR, Imagen a PDF', 'Texto reconocido: '.mb_substr(trim(preg_replace('/\s+/', ' ', $text->getOutput())), 0, 40));
        } catch (\Throwable $exception) {
            return $this->row('OCR (OCRmyPDF + Tesseract spa)', false, 'OCR, Imagen a PDF', mb_substr($exception->getMessage(), 0, 120));
        } finally {
            array_map('unlink', glob($directory.'/*') ?: []);
            rmdir($directory);
        }
    }

    private function queue(): array
    {
        $retryAfter = (int) config('queue.connections.'.config('queue.default').'.retry_after');
        $needed = OcrPdfTool::ocrTimeout();

        return $this->row('Cola: retry_after', $retryAfter > $needed, 'OCR de PDF largos', "retry_after = {$retryAfter} s; debe superar {$needed} s (DB_QUEUE_RETRY_AFTER).");
    }

    /** PHP's own limits: those of this console. The web server may use another php.ini; check it too. */
    private function uploads(): array
    {
        $limit = UploadedFile::getMaxFilesize();

        return $this->row('PHP: tamaño de subida', $limit >= 50 * 1024 * 1024, 'OCR, Comprimir (50 MB)', 'Límite en esta consola: '.round($limit / 1048576).' MB (el php.ini de Apache puede ser otro).');
    }

    private function row(string $name, bool $ok, string $usedBy, string $detail): array
    {
        $this->failed = $this->failed || ! $ok;

        return [$name, $ok ? '<info>OK</info>' : '<error>FALTA</error>', $usedBy, $detail];
    }
}
