<?php

namespace App\Jobs;

use App\Models\FileConversion;
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
            if ($conversion->format === 'pdf') {
                $this->convertPdf($input, $output);
            } else {
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
                'finished_at' => now(),
                'expires_at' => now()->addHours(config('conversions.download_hours')),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($outputPath);
            $conversion->update(['status' => 'failed', 'error_message' => $exception instanceof \InvalidArgumentException ? $exception->getMessage() : 'No se pudo convertir el archivo. Comprueba que no esté dañado e inténtalo de nuevo.', 'finished_at' => now()]);
            Log::warning('File conversion failed', ['conversion_id' => $conversion->id, 'error' => $exception->getMessage()]);
        }
    }

    private function convertPdf(string $input, string $output): void
    {
        $directory = dirname($output);
        $converted = $directory.'/'.pathinfo($input, PATHINFO_FILENAME).'.docx';
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

            if (! $process->isSuccessful() || ! is_file($converted) || filesize($converted) === 0) {
                throw new \RuntimeException('LibreOffice: '.mb_substr($process->getErrorOutput().$process->getOutput(), 0, 500));
            }

            $archive = new \ZipArchive;
            if ($archive->open($converted) !== true) {
                throw new \RuntimeException('LibreOffice no generó un documento Word válido.');
            }

            $xml = $archive->getFromName('word/document.xml');
            $archive->close();
            $word = new \DOMDocument;
            if ($xml === false || ! $word->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('LibreOffice no generó un documento Word válido.');
            }

            // ponytail: text presence is the minimum check; mixed scanned/text PDFs would need OCR detection.
            $hasText = false;
            foreach ($word->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $text) {
                if (trim($text->textContent) !== '') {
                    $hasText = true;
                    break;
                }
            }

            if (! $hasText) {
                throw new \InvalidArgumentException('El PDF no contiene texto editable. Los PDF escaneados requieren OCR.');
            }

            if (! rename($converted, $output)) {
                throw new \RuntimeException('No se pudo guardar el documento Word.');
            }
        } finally {
            if (is_file($converted)) {
                unlink($converted);
            }
        }
    }

    public function failed(?\Throwable $exception): void
    {
        FileConversion::whereKey($this->conversionId)->whereIn('status', ['queued', 'processing'])->update([
            'status' => 'failed', 'error_message' => 'La conversión no pudo completarse. Inténtalo de nuevo.', 'finished_at' => now(),
        ]);
    }
}
