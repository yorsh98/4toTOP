<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\AudioInspector;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class AudioCutTool extends ConversionTool
{
    public const ENCODERS = [
        'mp3' => ['label' => 'MP3', 'args' => ['-codec:a', 'libmp3lame', '-b:a', '192k']],
        'm4a' => ['label' => 'M4A (AAC)', 'args' => ['-codec:a', 'aac', '-b:a', '192k', '-movflags', '+faststart']],
        'wav' => ['label' => 'WAV', 'args' => ['-codec:a', 'pcm_s16le']],
        'ogg' => ['label' => 'OGG', 'args' => ['-codec:a', 'libvorbis', '-q:a', '5']],
        'flac' => ['label' => 'FLAC', 'args' => ['-codec:a', 'flac']],
    ];

    public function needsAudioProbe(): bool
    {
        return true;
    }

    public function resultExtension(): string
    {
        return 'mp3';
    }

    public function resultSuffix(): string
    {
        return '_recortado';
    }

    public function defaults(): array
    {
        return ['segments' => '[]', 'mode' => 'keep', 'separate' => false, 'fade_ms' => 0, 'normalize' => false, 'output' => 'mp3'];
    }

    public function rules(): array
    {
        return [
            'segments' => ['required', 'string', 'max:20000'],
            'mode' => ['required', 'in:keep,remove,silence'],
            'separate' => ['boolean'],
            'fade_ms' => ['required', 'integer', 'in:0,100,500,2000'],
            'normalize' => ['boolean'],
            'output' => ['required', 'in:'.implode(',', array_keys(self::ENCODERS))],
        ];
    }

    /** @return list<array{0: float, 1: float}> */
    public static function spans(array $regions, string $mode, float $duration): array
    {
        if (! array_is_list($regions)) {
            throw new \InvalidArgumentException('Las regiones no son válidas. Vuelve a marcarlas.');
        }
        foreach ($regions as $index => &$region) {
            if (! is_array($region) || ! array_is_list($region) || count($region) !== 2
                || ! is_numeric($region[0]) || ! is_numeric($region[1])
                || is_string($region[0]) || is_string($region[1])
                || ! is_finite((float) $region[0]) || ! is_finite((float) $region[1])) {
                throw new \InvalidArgumentException('Las regiones no son válidas. Vuelve a marcarlas.');
            }
            [$start, $end] = array_map(fn ($time) => round($time, 3), $region);
            $number = $index + 1;
            if ($start < -0.05 || $end > $duration + 0.05) {
                throw new \InvalidArgumentException('La región '.$number.' ('.AudioInspector::clock($start).'–'.AudioInspector::clock($end).') está fuera del audio, que dura '.AudioInspector::clock($duration).'.');
            }
            $region = [max(0.0, $start), min($duration, $end)];
            if (0.05 - 0.0000001 > $region[1] - $region[0]) {
                throw new \InvalidArgumentException('La región '.$number.' es demasiado corta.');
            }
        }
        unset($region);
        if ($regions === []) {
            throw new \InvalidArgumentException('Marca al menos una región en la onda.');
        }
        $limit = config('conversions.audio_cut_max_segments');
        if (count($regions) > $limit) {
            throw new \InvalidArgumentException('Puedes marcar hasta '.$limit.' regiones.');
        }
        usort($regions, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($regions as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($end, $merged[$last][1]);
            } else {
                $merged[] = [$start, $end];
            }
        }
        if ($mode === 'remove') {
            $remaining = [];
            $cursor = 0.0;
            foreach ($merged as [$start, $end]) {
                if ($start - $cursor >= 0.01) {
                    $remaining[] = [$cursor, $start];
                }
                $cursor = $end;
            }
            if ($duration - $cursor >= 0.01) {
                $remaining[] = [$cursor, $duration];
            }
            $merged = $remaining;
        }
        if ($mode !== 'silence' && self::total($merged) < 0.1 - 0.0000001) {
            throw new \InvalidArgumentException('El resultado quedaría vacío.');
        }

        return $merged;
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        try {
            $duration = app(AudioInspector::class)->probe($files[0]->getRealPath())['duration'];
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $regions = json_decode($options['segments'], true, 4);
        try {
            if (! is_array($regions) || ! array_is_list($regions)) {
                throw new \InvalidArgumentException('Las regiones no son válidas. Vuelve a marcarlas.');
            }
            $spans = self::spans($regions, $options['mode'], $duration);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['options.segments' => $exception->getMessage()]);
        }
        $separate = $options['mode'] === 'keep' && (bool) $options['separate'];
        $fade = (int) $options['fade_ms'];
        if ($options['mode'] !== 'silence' && ($separate || count($spans) > 1) && $fade > 0) {
            foreach ($spans as $index => [$start, $end]) {
                if ($end - $start < 2 * $fade / 1000) {
                    throw ValidationException::withMessages(['options.fade_ms' => 'El fundido es más largo que el tramo '.($index + 1).'; elige uno más corto.']);
                }
            }
        }

        return ['mode' => $options['mode'], 'spans' => $spans, 'separate' => $separate, 'fade_ms' => $fade, 'normalize' => (bool) $options['normalize'], 'output' => $options['output'], 'duration' => $duration];
    }

    public function timeout(array $options): int
    {
        return max(360, (int) ceil(($options['duration'] ?? 0) / 10) + 120);
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'ffmpeg';
        $input = $directory.'/'.basename($conversion->input_path);
        $o = $conversion->options;
        $inspector = app(AudioInspector::class);
        $source = $inspector->probe($input);
        $encoder = self::ENCODERS[$o['output']]['args'];
        $base = [config('conversions.ffmpeg'), '-nostdin', '-y', '-v', 'error'];
        $fade = $o['fade_ms'] / 1000;
        // loudnorm upsamples internally; restore the source rate instead of exporting at 192 kHz.
        $normalize = $o['normalize'] ? ',loudnorm=I=-16:TP=-1.5:LRA=11,aresample='.$source['sample_rate'] : '';
        $spans = $o['spans'];
        $count = count($spans);

        if ($o['separate']) {
            $work = $directory.'/cut';
            $name = Str::limit(pathinfo(basename($conversion->original_name), PATHINFO_FILENAME), 120, '');
            File::ensureDirectoryExists($work, 0700);
            $parts = [];
            try {
                foreach ($spans as $index => [$start, $end]) {
                    $filename = sprintf('%s_%02d.%s', $name, $index + 1, $o['output']);
                    $filter = $fade > 0 ? 'afade=t=in:d='.self::decimal($fade).',afade=t=out:st='.self::decimal($end - $start - $fade).':d='.self::decimal($fade) : 'anull';
                    $this->run([...$base, '-ss', self::decimal($start), '-to', self::decimal($end), '-i', $input, '-map', '0:a:0', '-vn', '-af', $filter.$normalize, ...$encoder, $work.'/'.$filename], $o);
                    $this->checkDuration($work.'/'.$filename, $end - $start);
                    $parts[$filename] = $work.'/'.$filename;
                }

                return new ToolResult(SplitPdfTool::deliver($parts, $directory, $o['output']), 'Se crearon '.$count.' archivos.');
            } finally {
                File::deleteDirectory($work);
            }
        }

        $output = $directory.'/result.'.$o['output'];
        if ($o['mode'] === 'silence') {
            $expression = implode('+', array_map(fn ($span) => 'between(t,'.self::decimal($span[0]).','.self::decimal($span[1]).')', $spans));
            $filters = ['-map', '0:a:0', '-vn', '-af', "volume=enable='{$expression}':volume=0".$normalize];
            $expected = $o['duration'];
            $notice = 'Se silenciaron '.$count.' regiones; la duración no cambia.';
        } else {
            $graph = [];
            foreach ($spans as $index => [$start, $end]) {
                $graph[] = '[0:a:0]atrim=start='.self::decimal($start).':end='.self::decimal($end).',asetpts=PTS-STARTPTS[a'.$index.']';
            }
            $last = 'a0';
            if ($count > 1 && $fade > 0) {
                for ($i = 1; $i < $count; $i++) {
                    $graph[] = '['.$last.'][a'.$i.']acrossfade=d='.self::decimal($fade).':c1=tri:c2=tri[x'.$i.']';
                    $last = 'x'.$i;
                }
            } elseif ($count > 1) {
                $graph[] = implode('', array_map(fn ($i) => '[a'.$i.']', array_keys($spans))).'concat=n='.$count.':v=0:a=1[cat]';
                $last = 'cat';
            }
            $graph[] = '['.$last.']anull'.$normalize.'[out]';
            $filters = ['-filter_complex', implode(';', $graph), '-map', '[out]'];
            $expected = self::total($spans) - ($count - 1) * $fade;
            $notice = $o['mode'] === 'keep'
                ? 'Se conservaron '.$count.' regiones.'
                : 'Se quitaron '.AudioInspector::clock($o['duration'] - self::total($spans)).' de audio.';
            $notice .= ' Duración final: '.AudioInspector::clock($expected).'.';
        }
        $this->run([...$base, '-i', $input, ...$filters, ...$encoder, $output], $o);
        $this->checkDuration($output, $expected);

        return new ToolResult($output, $notice);
    }

    private function run(array $command, array $options): void
    {
        $process = new Process($command);
        $process->setTimeout($this->timeout($options));
        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('FFmpeg: '.mb_substr($process->getErrorOutput(), 0, 500));
        }
    }

    private function checkDuration(string $path, float $expected): void
    {
        if (abs(app(AudioInspector::class)->probe($path)['duration'] - $expected) > 0.15) {
            throw new \RuntimeException('La duración del audio cortado no coincide con lo marcado.');
        }
    }

    private static function total(array $spans): float
    {
        return array_sum(array_map(fn ($span) => $span[1] - $span[0], $spans));
    }

    private static function decimal(float $value): string
    {
        return sprintf('%.3F', $value);
    }
}
