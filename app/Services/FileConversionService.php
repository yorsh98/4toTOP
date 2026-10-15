<?php

namespace App\Services;

use App\Jobs\ConvertFile;
use App\Models\FileConversion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileConversionService
{
    public function create(UploadedFile $file, string $format, string $area, string $actorName): FileConversion
    {
        $sessionHash = hash('sha256', session()->getId());
        $disk = Storage::disk(config('conversions.disk'));

        return Cache::lock('file-conversions:'.$sessionHash, 15)->block(5, function () use ($file, $format, $area, $actorName, $sessionHash, $disk) {
            if ($area === 'frontoffice' && $this->used($sessionHash, $format) >= config('conversions.public_limit')) {
                throw ValidationException::withMessages(['file' => 'Ya usaste las dos conversiones de este formato en esta sesión.']);
            }

            $token = (string) Str::uuid();
            $inputPath = 'conversions/'.$token.'/original.'.$format;
            $outputName = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 240, '').($format === 'pdf' ? '.docx' : '.mp3');

            if (! $disk->putFileAs('conversions/'.$token, $file, basename($inputPath))) {
                throw new \RuntimeException('No se pudo guardar el archivo para su conversión.');
            }

            try {
                $conversion = FileConversion::create([
                    'token' => $token,
                    'area' => $area,
                    'format' => $format,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
                    'result_name' => $outputName,
                    'input_path' => $inputPath,
                    'user_id' => $area === 'backoffice' ? auth()->id() : null,
                    'actor_name' => $actorName,
                    'ip_address' => request()->ip(),
                    'session_hash' => $sessionHash,
                    'user_agent' => request()->userAgent(),
                ]);

                ConvertFile::dispatch($conversion->id);

                return $conversion;
            } catch (\Throwable $exception) {
                $disk->deleteDirectory('conversions/'.$token);
                throw $exception;
            }
        });
    }

    public function used(string $sessionHash, string $format): int
    {
        return FileConversion::query()
            ->where('area', 'frontoffice')
            ->where('session_hash', $sessionHash)
            ->where('format', $format)
            ->whereIn('status', ['queued', 'processing', 'ready'])
            ->count();
    }
}
