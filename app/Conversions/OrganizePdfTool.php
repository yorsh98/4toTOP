<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Validation\ValidationException;

/** Reorders, rotates and removes pages. The page draws the thumbnails in the browser with pdf.js and sends the plan. */
class OrganizePdfTool extends ConversionTool
{
    /** Thumbnails are drawn in the browser; more pages than this make it slow and memory hungry. */
    public const MAX_PAGES = 300;

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
        return '_organizado';
    }

    public function defaults(): array
    {
        return ['plan' => ''];
    }

    public function rules(): array
    {
        return ['plan' => ['required', 'string', 'max:100000']];
    }

    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        if ($pageCount > self::MAX_PAGES) {
            throw ValidationException::withMessages(['file' => 'Organizar acepta PDF de hasta '.self::MAX_PAGES.' páginas.']);
        }

        return ['plan' => self::plan($options['plan'], $pageCount)];
    }

    /** @return list<array{page: int, rotation: int}> */
    public static function plan(string|array $plan, int $pageCount): array
    {
        $items = is_array($plan) ? $plan : json_decode($plan, true);
        if (! is_array($items) || $items === [] || ! array_is_list($items)) {
            throw ValidationException::withMessages(['options.plan' => 'Deja al menos una página en el PDF.']);
        }

        return array_map(function ($item) use ($pageCount) {
            $page = filter_var($item['page'] ?? null, FILTER_VALIDATE_INT);
            $rotation = filter_var($item['rotation'] ?? 0, FILTER_VALIDATE_INT);
            if ($page === false || $page < 1 || $page > $pageCount || ! in_array($rotation, [0, 90, 180, 270], true)) {
                throw ValidationException::withMessages(['options.plan' => 'El orden de páginas no es válido; vuelve a cargar el PDF.']);
            }

            return ['page' => $page, 'rotation' => $rotation];
        }, $items);
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $input = $directory.'/'.basename($conversion->input_path);
        $plan = self::plan($conversion->options['plan'], app(PdfInspector::class)->pages($input));
        $flattened = app(PdfPageTools::class)->organize($input, $directory.'/result.pdf', $plan);

        return new ToolResult($directory.'/result.pdf', self::flattenedNotice($flattened));
    }
}
