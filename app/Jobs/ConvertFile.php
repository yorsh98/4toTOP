<?php

namespace App\Jobs;

use App\Models\FileConversion;
use App\Services\LibreOfficeDocxNormalizer;
use App\Services\PdfInspector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Uri\Uri;
use Symfony\Component\Process\Process;

class ConvertFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 360;

    public int $tries = 1;

    private ?string $notice = null;

    private ?string $engine = null;

    public function __construct(public int $conversionId) {}

    public function handle(): void
    {
        $conversion = FileConversion::findOrFail($this->conversionId);
        $conversion->update(['status' => 'processing']);
        $disk = Storage::disk(config('conversions.disk'));
        $extension = $conversion->format === 'pdf' ? 'docx' : 'mp3';
        $outputPath = 'conversions/'.$conversion->token.'/result.'.$extension;

        try {
            $input = $disk->path($conversion->input_path);
            $output = $disk->path($outputPath);
            $conversion->update(['input_sha256' => hash_file('sha256', $input)]);
            if ($conversion->format === 'pdf') {
                $this->convertPdf($input, $output);
            } else {
                $this->engine = 'ffmpeg';
                $process = new Process([config('conversions.ffmpeg'), '-nostdin', '-y', '-i', $input, '-vn', '-codec:a', 'libmp3lame', '-b:a', '192k', $output]);
                $process->setTimeout(300);
                $process->run();

                if (! $process->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg: '.mb_substr($process->getErrorOutput(), 0, 500));
                }
            }

            if (! $disk->exists($outputPath) || $disk->size($outputPath) === 0) {
                throw new \RuntimeException('El archivo convertido está vacío.');
            }

            $conversion->update([
                'status' => 'ready',
                'output_path' => $outputPath,
                'engine' => $this->engine,
                'notice' => $this->notice,
                'output_sha256' => hash_file('sha256', $output),
                'finished_at' => now(),
                'expires_at' => now()->addHours(config('conversions.download_hours')),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($outputPath);
            $conversion->update(['status' => 'failed', 'engine' => $this->engine, 'error_message' => $exception instanceof \InvalidArgumentException ? $exception->getMessage() : 'No se pudo convertir el archivo. Comprueba que no esté dañado e inténtalo de nuevo.', 'finished_at' => now()]);
            Log::warning('File conversion failed', ['conversion_id' => $conversion->id, 'engine' => $this->engine, 'error' => $exception->getMessage()]);
        }
    }

    private function convertPdf(string $input, string $output): void
    {
        $directory = dirname($output);
        $pdf = app(PdfInspector::class)->inspect($input);
        $scanned = $pdf['pages_without_text'];
        $ocrPath = $directory.'/ocr.pdf';
        $converted = $directory.'/converted.docx';

        try {
            if ($scanned !== [] && config('conversions.ocrmypdf')) {
                $this->runOcr($input, $ocrPath);
                $input = $ocrPath;
                $this->notice = 'Se reconoció texto (OCR) en '.$this->pageList($scanned).'. Revisa el texto reconocido antes de usarlo.';
            } elseif (count($scanned) === $pdf['pages']) {
                throw new \InvalidArgumentException('El PDF no contiene texto editable. Los PDF escaneados requieren OCR.');
            } elseif ($scanned !== []) {
                $this->notice = 'No hay texto seleccionable en '.$this->pageList($scanned).' (posiblemente escaneadas): se incluyen como imagen, sin texto editable.';
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
        $directory = dirname($converted);
        $libreOfficeOutput = $directory.'/'.pathinfo($input, PATHINFO_FILENAME).'.docx';
        $profilePath = $directory.'/lo-profile';
        $profileUri = PHP_OS_FAMILY === 'Windows'
            ? Uri::fromWindowsPath($profilePath)
            : Uri::fromUnixPath($profilePath);

        try {
            $process = new Process([
                config('conversions.libreoffice'),
                '-env:UserInstallation='.$profileUri,
                '--headless', '--infilter=writer_pdf_import',
                '--convert-to', 'docx:Office Open XML Text',
                '--outdir', $directory, $input,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($libreOfficeOutput) || filesize($libreOfficeOutput) === 0) {
                throw new \RuntimeException('LibreOffice: '.mb_substr($process->getErrorOutput().$process->getOutput(), 0, 500));
            }

            app(LibreOfficeDocxNormalizer::class)->normalize($libreOfficeOutput);

            if (! rename($libreOfficeOutput, $converted)) {
                throw new \RuntimeException('No se pudo guardar el documento Word.');
            }
        } finally {
            if (is_file($libreOfficeOutput)) {
                unlink($libreOfficeOutput);
            }
        }
    }

    private function runOcr(string $input, string $output): void
    {
        $process = new Process([
            config('conversions.ocrmypdf'), '--skip-text', '--rotate-pages', '--deskew',
            '-l', config('conversions.ocr_languages'), '--output-type', 'pdf', $input, $output,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            throw new \RuntimeException('OCRmyPDF: '.mb_substr($process->getErrorOutput(), -500));
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

    private function pageList(array $pages): string
    {
        $shown = implode(', ', array_slice($pages, 0, 10)).(count($pages) > 10 ? '…' : '');

        return (count($pages) === 1 ? 'la página ' : 'las páginas ').$shown;
    }

    public function failed(?\Throwable $exception): void
    {
        FileConversion::whereKey($this->conversionId)->whereIn('status', ['queued', 'processing'])->update([
            'status' => 'failed', 'error_message' => 'La conversión no pudo completarse. Inténtalo de nuevo.', 'finished_at' => now(),
        ]);
    }
}
