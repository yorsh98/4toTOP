<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Validation\ValidationException;

class PageNumbersTool extends ConversionTool
{
    public const POSITIONS = [
        'top-left' => 'Arriba a la izquierda', 'top-center' => 'Arriba al centro', 'top-right' => 'Arriba a la derecha',
        'bottom-left' => 'Abajo a la izquierda', 'bottom-center' => 'Abajo al centro', 'bottom-right' => 'Abajo a la derecha',
    ];

    public const FORMATS = ['n' => '{n}', 'page' => 'Página {n}', 'page_total' => 'Página {n} de {total}', 'folio' => 'Fs. {n}'];

    public const MARGINS = ['small' => 'Pequeño', 'medium' => 'Mediano', 'large' => 'Grande'];

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
        return '_numerado';
    }

    public function defaults(): array
    {
        return ['position' => 'bottom-center', 'margin' => 'medium', 'format' => 'n', 'start' => 1, 'first' => 1, 'last' => null, 'size' => 11];
    }

    public function rules(): array
    {
        return [
            'position' => ['required', 'in:'.implode(',', array_keys(self::POSITIONS))],
            'margin' => ['required', 'in:'.implode(',', array_keys(self::MARGINS))],
            'format' => ['required', 'in:'.implode(',', array_keys(self::FORMATS))],
            'start' => ['required', 'integer', 'min:0', 'max:999999'],
            'first' => ['required', 'integer', 'min:1'],
            'last' => ['nullable', 'integer', 'min:1'],
            'size' => ['required', 'integer', 'min:6', 'max:36'],
        ];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        $last = $options['last'] ?: $pageCount;
        if ($options['first'] > $pageCount) {
            throw ValidationException::withMessages(['options.first' => "El PDF tiene {$pageCount} páginas."]);
        }
        if ($last < $options['first'] || $last > $pageCount) {
            throw ValidationException::withMessages(['options.last' => "Indica una página entre {$options['first']} y {$pageCount}."]);
        }

        return [
            'position' => $options['position'], 'margin' => $options['margin'], 'format' => $options['format'],
            'start' => (int) $options['start'], 'first' => (int) $options['first'], 'last' => (int) $last, 'size' => (int) $options['size'],
        ];
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $input = $directory.'/'.basename($conversion->input_path);
        $options = $conversion->options;
        if ($options['last'] > app(PdfInspector::class)->pages($input)) {
            throw new \InvalidArgumentException('El rango de páginas no coincide con el PDF.');
        }

        $flattened = app(PdfPageTools::class)->number($input, $directory.'/result.pdf', ['format' => self::FORMATS[$options['format']]] + $options);

        return new ToolResult($directory.'/result.pdf', self::flattenedNotice($flattened));
    }
}
