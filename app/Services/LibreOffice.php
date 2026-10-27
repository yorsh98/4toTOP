<?php

namespace App\Services;

use League\Uri\Uri;
use Symfony\Component\Process\Process;

/** Runs headless LibreOffice with a private profile per job, so concurrent jobs do not share (or lock) one. */
class LibreOffice
{
    /**
     * Converts $input into $directory and returns the produced file, named after the input with $extension.
     *
     * @param  list<string>  $options  extra arguments placed before --convert-to, such as an import filter
     */
    public function convert(string $input, string $directory, string $convertTo, string $extension, array $options = []): string
    {
        $output = $directory.'/'.pathinfo($input, PATHINFO_FILENAME).'.'.$extension;
        $profilePath = $directory.'/lo-profile';
        $profileUri = PHP_OS_FAMILY === 'Windows' ? Uri::fromWindowsPath($profilePath) : Uri::fromUnixPath($profilePath);

        $process = new Process([
            config('conversions.libreoffice'), '-env:UserInstallation='.$profileUri, '--headless',
            ...$options, '--convert-to', $convertTo, '--outdir', $directory, $input,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            if (is_file($output)) {
                unlink($output);
            }
            throw new \RuntimeException('LibreOffice: '.mb_substr($process->getErrorOutput().$process->getOutput(), 0, 500));
        }

        return $output;
    }
}
