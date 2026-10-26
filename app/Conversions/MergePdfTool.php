<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\PdfInspector;
use App\Services\PdfPageTools;
use Illuminate\Support\Facades\Storage;

class MergePdfTool extends ConversionTool
{
    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function resultSuffix(): string
    {
        return '_unido';
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'pymupdf';
        $disk = Storage::disk(config('conversions.disk'));
        $inputs = $conversion->options['inputs'];
        foreach ($inputs as $index => $input) {
            try {
                app(PdfInspector::class)->pages($disk->path($input['path']));
            } catch (\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException('«'.$input['name'].'»: '.$exception->getMessage());
            }
            $inputs[$index]['sha256'] = hash_file('sha256', $disk->path($input['path']));
        }
        $conversion->update(['options' => ['inputs' => $inputs] + $conversion->options]);

        $output = $directory.'/result.pdf';
        $flattened = app(PdfPageTools::class)->merge(array_map(fn (array $input) => $disk->path($input['path']), $inputs), $output);

        return new ToolResult($output, self::flattenedNotice($flattened));
    }
}
