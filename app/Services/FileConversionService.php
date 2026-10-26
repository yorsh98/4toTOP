<?php

namespace App\Services;

use App\Conversions\ConversionTool;
use App\Jobs\ConvertFile;
use App\Models\FileConversion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileConversionService
{
    /**
     * Stores the uploaded files and queues the tool. Tools that take several files keep them, in order,
     * in options.inputs; the others store their single file as original.<extension>.
     *
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $options  as returned by the tool's prepare()
     */
    public function create(array $files, string $format, string $area, string $actorName, array $options = []): FileConversion
    {
        $sessionHash = hash('sha256', session()->getId());
        $disk = Storage::disk(config('conversions.disk'));
        $tool = ConversionTool::for($format);

        return Cache::lock('file-conversions:'.$sessionHash, 15)->block(5, function () use ($files, $format, $tool, $area, $actorName, $options, $sessionHash, $disk) {
            if ($area === 'frontoffice' && $this->used($sessionHash, $format) >= config('conversions.public_limit')) {
                throw ValidationException::withMessages(['file' => 'Ya usaste los dos usos de esta herramienta en esta sesión.']);
            }

            $token = (string) Str::uuid();
            $directory = 'conversions/'.$token;
            $names = array_map(fn (UploadedFile $file) => Str::limit(basename($file->getClientOriginalName()), 255, ''), $files);
            $outputName = Str::limit(pathinfo($names[0], PATHINFO_FILENAME), 220, '').$tool->resultSuffix().'.'.$tool->resultExtension();
            $extension = fn (UploadedFile $file) => in_array($found = strtolower($file->getClientOriginalExtension()), $tool->config['accept'], true) ? $found : $tool->config['accept'][0];

            $paths = [];
            foreach ($files as $index => $file) {
                $paths[] = $directory.'/'.($tool->multiple() ? sprintf('input-%02d', $index + 1) : 'original').'.'.$extension($file);
            }
            if ($tool->multiple()) {
                $options['inputs'] = array_map(fn (string $path, string $name) => ['path' => $path, 'name' => $name], $paths, $names);
            }

            foreach ($files as $index => $file) {
                if (! $disk->putFileAs($directory, $file, basename($paths[$index]))) {
                    $disk->deleteDirectory($directory);
                    throw new \RuntimeException('No se pudo guardar el archivo para su conversión.');
                }
            }

            try {
                $conversion = FileConversion::create([
                    'token' => $token,
                    'area' => $area,
                    'format' => $format,
                    'original_name' => count($names) > 1 ? Str::limit($names[0], 230, '').' + '.(count($names) - 1).' más' : $names[0],
                    'result_name' => $outputName,
                    'input_path' => $paths[0],
                    'options' => $options ?: null,
                    'user_id' => $area === 'backoffice' ? auth()->id() : null,
                    'actor_name' => $actorName,
                    'ip_address' => request()->ip(),
                    'session_hash' => $sessionHash,
                    'user_agent' => request()->userAgent(),
                ]);

                $job = new ConvertFile($conversion->id);
                $job->timeout = $tool->timeout($options);
                dispatch($job);

                return $conversion;
            } catch (\Throwable $exception) {
                $disk->deleteDirectory($directory);
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
