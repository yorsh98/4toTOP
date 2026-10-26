<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Support\Number;
use Symfony\Component\Process\Process;

class CompressPdfTool extends ConversionTool
{
    /** Ghostscript presets, as iLovePDF's extreme / recommended / low compression. */
    public const LEVELS = ['extreme' => '/screen', 'recommended' => '/ebook', 'low' => '/printer'];

    /** A result that does not save at least this share is not worth losing the original for. */
    private const MIN_SAVING = 0.05;

    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function resultSuffix(): string
    {
        return '_comprimido';
    }

    public function defaults(): array
    {
        return ['level' => 'recommended'];
    }

    public function rules(): array
    {
        return ['level' => ['required', 'in:'.implode(',', array_keys(self::LEVELS))]];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        return ['level' => $options['level']];
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'ghostscript';
        $input = $directory.'/'.basename($conversion->input_path);
        $output = $directory.'/result.pdf';
        $baked = $directory.'/baked.pdf';
        $pages = app(PdfInspector::class)->pages($input);

        try {
            $flattened = app(PdfPageTools::class)->bake($input, $baked);
            $process = new Process([
                config('conversions.ghostscript'), '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.6',
                '-dPDFSETTINGS='.self::LEVELS[$conversion->options['level']], '-dDetectDuplicateImages=true',
                '-dNOPAUSE', '-dBATCH', '-dSAFER', '-dQUIET', '-sOutputFile='.$output, $baked,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
                throw new \RuntimeException('Ghostscript: '.mb_substr($process->getErrorOutput().$process->getOutput(), -500));
            }
        } finally {
            if (is_file($baked)) {
                unlink($baked);
            }
        }

        if (app(PdfInspector::class)->pages($output) !== $pages) {
            throw new \RuntimeException('Ghostscript cambió el número de páginas.');
        }

        $before = filesize($input);
        $after = filesize($output);
        if ($after > $before * (1 - self::MIN_SAVING)) {
            // The untouched original keeps its signature fields and their validity.
            copy($input, $output);

            return new ToolResult($output, 'El PDF ya estaba optimizado: se entrega sin cambios.');
        }

        return new ToolResult($output, trim('Pasó de '.Number::fileSize($before, 1).' a '.Number::fileSize($after, 1).' (−'.round(100 * (1 - $after / $before)).' %). '.self::flattenedNotice($flattened)));
    }
}
