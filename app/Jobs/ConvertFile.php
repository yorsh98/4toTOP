<?php

namespace App\Jobs;

use App\Conversions\ConversionTool;
use App\Models\FileConversion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Runs one tool of /convertir-archivos (see App\Conversions) and records its result. */
class ConvertFile implements ShouldQueue
{
    use Queueable;

    /** Raised per job by FileConversionService for tools that need longer, such as OCR. */
    public int $timeout = 360;

    public int $tries = 1;

    public function __construct(public int $conversionId) {}

    public function handle(): void
    {
        $conversion = FileConversion::findOrFail($this->conversionId);
        $conversion->update(['status' => 'processing']);
        $disk = Storage::disk(config('conversions.disk'));
        $directory = 'conversions/'.$conversion->token;
        $tool = null;

        try {
            $tool = ConversionTool::for($conversion->format);
            $conversion->update(['input_sha256' => hash_file('sha256', $disk->path($conversion->input_path))]);
            $result = $tool->process($conversion, $disk->path($directory));
            $outputPath = $directory.'/'.basename($result->path);

            if (! is_file($result->path) || filesize($result->path) === 0) {
                throw new \RuntimeException('El archivo convertido está vacío.');
            }

            // A tool may deliver another type than announced (a split with one part is a PDF, not a ZIP).
            $extension = pathinfo($result->path, PATHINFO_EXTENSION);
            $conversion->update([
                'status' => 'ready',
                'result_name' => pathinfo($conversion->result_name, PATHINFO_FILENAME).'.'.$extension,
                'output_path' => $outputPath,
                'engine' => $tool->engine,
                'notice' => $result->notice === null ? null : mb_substr($result->notice, 0, 500),
                'output_sha256' => hash_file('sha256', $result->path),
                'finished_at' => now(),
                'expires_at' => now()->addHours(config('conversions.download_hours')),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete(array_filter($disk->files($directory), fn (string $path) => str_starts_with(basename($path), 'result.')));
            $conversion->update(['status' => 'failed', 'engine' => $tool?->engine, 'error_message' => $exception instanceof \InvalidArgumentException ? $exception->getMessage() : 'No se pudo procesar el archivo. Comprueba que no esté dañado e inténtalo de nuevo.', 'finished_at' => now()]);
            Log::warning('File conversion failed', ['conversion_id' => $conversion->id, 'engine' => $tool?->engine, 'error' => $exception->getMessage()]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        FileConversion::whereKey($this->conversionId)->whereIn('status', ['queued', 'processing'])->update([
            'status' => 'failed', 'error_message' => 'La conversión no pudo completarse. Inténtalo de nuevo.', 'finished_at' => now(),
        ]);
    }
}
