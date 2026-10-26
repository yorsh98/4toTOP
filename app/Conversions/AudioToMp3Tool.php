<?php

namespace App\Conversions;

use App\Models\FileConversion;
use Symfony\Component\Process\Process;

class AudioToMp3Tool extends ConversionTool
{
    public function resultExtension(): string
    {
        return 'mp3';
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'ffmpeg';
        $output = $directory.'/result.mp3';
        $process = new Process([config('conversions.ffmpeg'), '-nostdin', '-y', '-i', $directory.'/'.basename($conversion->input_path), '-vn', '-codec:a', 'libmp3lame', '-b:a', '192k', $output]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('FFmpeg: '.mb_substr($process->getErrorOutput(), 0, 500));
        }

        return new ToolResult($output);
    }
}
