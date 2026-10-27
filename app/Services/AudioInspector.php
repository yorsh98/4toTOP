<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class AudioInspector
{
    /** Without this, a missing ffprobe (exit 127) made every file look like "no readable audio". */
    private function assertInstalled(string $program): void
    {
        $command = (string) config('conversions.'.$program);
        $found = str_contains($command, '/') || str_contains($command, '\\')
            ? is_file($command) && is_executable($command)
            : (new ExecutableFinder)->find($command) !== null;

        if (! $found) {
            Log::error('Audio cutter: '.$program.' not found', ['configured' => $command]);

            throw new \InvalidArgumentException('El cortador de audio no está disponible: el servidor no tiene FFmpeg instalado o configurado. Avisa al administrador del sistema.');
        }
    }

    /** @return array{duration: float, channels: int, sample_rate: int, codec: string} */
    public function probe(string $path): array
    {
        $this->assertInstalled('ffprobe');
        $process = new Process([config('conversions.ffprobe'), '-v', 'error', '-select_streams', 'a:0', '-show_entries', 'stream=codec_name,channels,sample_rate,start_time:format=duration', '-of', 'json', $path]);
        $process->setTimeout(30);
        $process->run();
        $info = json_decode($process->getOutput(), true);
        $duration = (float) ($info['format']['duration'] ?? 0);
        // MP3 container duration includes encoder delay (138 ms at 8 kHz); playback skips it.
        if (($info['streams'][0]['codec_name'] ?? null) === 'mp3') {
            $duration -= max(0, (float) ($info['streams'][0]['start_time'] ?? 0));
        }
        if (! $process->isSuccessful() || empty($info['streams'][0]) || ! is_finite($duration) || $duration <= 0) {
            throw new \InvalidArgumentException('El archivo no contiene audio legible.');
        }
        if ($duration > config('conversions.audio_max_seconds')) {
            throw new \InvalidArgumentException('El audio dura '.self::clock($duration).'; el máximo es '.self::clock(config('conversions.audio_max_seconds')).'.');
        }

        return ['duration' => $duration, 'channels' => (int) $info['streams'][0]['channels'], 'sample_rate' => (int) $info['streams'][0]['sample_rate'], 'codec' => $info['streams'][0]['codec_name']];
    }

    /** @return array{per_second: int, peaks: string} One unsigned byte per peak, encoded as base64. */
    public function peaks(string $path, float $duration): array
    {
        $this->assertInstalled('ffmpeg');
        $perSecond = max(1, min(50, intdiv(200_000, max(1, (int) ceil($duration)))));
        $bucketSize = intdiv(8000, $perSecond);
        $process = new Process([config('conversions.ffmpeg'), '-nostdin', '-v', 'error', '-i', $path, '-map', '0:a:0', '-vn', '-ac', '1', '-ar', '8000', '-f', 's16le', '-']);
        $process->setTimeout(120);
        $process->start();
        $pending = $peaks = $error = '';
        $samples = $maximum = 0;

        // The iterator clears consumed output; retaining it would buffer hours of PCM.
        foreach ($process->getIterator() as $type => $chunk) {
            if ($type === Process::ERR) {
                $error = substr($error.$chunk, 0, 500);

                continue;
            }
            $chunk = $pending.$chunk;
            $length = strlen($chunk) - strlen($chunk) % 2;
            $pending = substr($chunk, $length);
            foreach (unpack('v*', substr($chunk, 0, $length)) as $sample) {
                $maximum = max($maximum, abs($sample >= 32768 ? $sample - 65536 : $sample));
                if (++$samples === $bucketSize) {
                    $peaks .= chr((int) round($maximum / 32768 * 255));
                    $samples = $maximum = 0;
                }
            }
        }
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('FFmpeg: '.$error);
        }
        if ($samples > 0) {
            $peaks .= chr((int) round($maximum / 32768 * 255));
        }

        return ['per_second' => $perSecond, 'peaks' => base64_encode($peaks)];
    }

    /** A duration limit in words: 14400 → "4 horas", 90 → "1:30.000". */
    public static function limit(int $seconds): string
    {
        return match (true) {
            $seconds === 3600 => '1 hora',
            $seconds % 3600 === 0 => intdiv($seconds, 3600).' horas',
            default => self::clock($seconds),
        };
    }

    public static function clock(float $seconds): string
    {
        $milliseconds = (int) round($seconds * 1000);
        $hours = intdiv($milliseconds, 3600000);

        return ($hours ? $hours.':'.sprintf('%02d', intdiv($milliseconds, 60000) % 60) : (string) intdiv($milliseconds, 60000))
            .sprintf(':%02d.%03d', intdiv($milliseconds, 1000) % 60, $milliseconds % 1000);
    }
}
