<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Validation\ValidationException;

class RotatePdfTool extends ConversionTool
{
    public function needsPageCount(): bool
    {
        return true;
    }

    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function resultSuffix(): string
    {
        return '_rotado';
    }

    public function defaults(): array
    {
        return ['scope' => 'all', 'ranges' => '', 'angle' => 90];
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', 'in:all,select'],
            'ranges' => ['nullable', 'string', 'max:2000'],
            'angle' => ['required', 'in:90,180,270'],
        ];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        if ($options['scope'] === 'select') {
            try {
                PdfPageTools::parseRanges((string) $options['ranges'], $pageCount);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['options.ranges' => $exception->getMessage()]);
            }
        }

        return ['scope' => $options['scope'], 'ranges' => $options['scope'] === 'select' ? (string) $options['ranges'] : '', 'angle' => (int) $options['angle']];
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $input = $directory.'/'.basename($conversion->input_path);
        $options = $conversion->options;
        $pageCount = app(PdfInspector::class)->pages($input);
        $pages = $options['scope'] === 'all'
            ? range(1, $pageCount)
            : array_values(array_unique(array_merge(...array_map(fn (array $range) => range($range[0], $range[1]), PdfPageTools::parseRanges($options['ranges'], $pageCount)))));

        // set_rotation only changes the page's /Rotate entry: the content is not redrawn.
        $flattened = app(PdfPageTools::class)->rotate($input, $directory.'/result.pdf', $pages, $options['angle']);

        return new ToolResult($directory.'/result.pdf', self::flattenedNotice($flattened));
    }
}
