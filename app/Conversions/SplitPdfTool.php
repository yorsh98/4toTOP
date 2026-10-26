<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SplitPdfTool extends ConversionTool
{
    public function needsPageCount(): bool
    {
        return true;
    }

    public function resultExtension(): string
    {
        return 'zip';
    }

    public function resultSuffix(): string
    {
        return '_dividido';
    }

    public function defaults(): array
    {
        return ['mode' => 'custom', 'ranges' => '', 'every' => 1, 'pages' => '', 'merge' => false];
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:'.implode(',', PdfPageTools::SPLIT_MODES)],
            'ranges' => ['nullable', 'string', 'max:2000'],
            'every' => ['nullable', 'integer', 'min:1'],
            'pages' => ['nullable', 'string', 'max:2000'],
            'merge' => ['boolean'],
        ];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        [$stored, $field] = match ($options['mode']) {
            'custom' => [['mode' => 'custom', 'ranges' => (string) $options['ranges'], 'merge' => (bool) $options['merge']], 'ranges'],
            'fixed' => [['mode' => 'fixed', 'every' => (int) $options['every']], 'every'],
            'extract_all' => [['mode' => 'extract_all'], 'mode'],
            'extract_select' => [['mode' => 'extract_select', 'pages' => (string) $options['pages'], 'merge' => (bool) $options['merge']], 'pages'],
        };

        try {
            PdfPageTools::splitGroups($stored, $pageCount);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['options.'.$field => $exception->getMessage()]);
        }

        return $stored;
    }

    /** A split that yields a single PDF is delivered as that PDF instead of a ZIP. */
    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $input = $directory.'/'.basename($conversion->input_path);
        $groups = PdfPageTools::splitGroups($conversion->options ?? [], app(PdfInspector::class)->pages($input));
        $workDirectory = $directory.'/split';
        $baseName = Str::limit(pathinfo($conversion->original_name, PATHINFO_FILENAME), 120, '');

        try {
            [$parts, $flattened] = app(PdfPageTools::class)->split($input, $groups, $workDirectory, $baseName);

            return new ToolResult(self::deliver($parts, $directory), self::flattenedNotice($flattened));
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /**
     * Moves a single part to result.<extension>, or packs several into result.zip.
     *
     * @param  array<string, string>  $parts  file name in the ZIP => path
     */
    public static function deliver(array $parts, string $directory, string $extension = 'pdf'): string
    {
        if (count($parts) === 1) {
            if (! rename(reset($parts), $directory.'/result.'.$extension)) {
                throw new \RuntimeException('No se pudo guardar el archivo.');
            }

            return $directory.'/result.'.$extension;
        }

        $archive = new \ZipArchive;
        if ($archive->open($directory.'/result.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el ZIP.');
        }
        foreach ($parts as $name => $path) {
            $archive->addFile($path, $name);
        }
        if (! $archive->close()) {
            throw new \RuntimeException('No se pudo crear el ZIP.');
        }

        return $directory.'/result.zip';
    }
}
