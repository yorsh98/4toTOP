<?php

namespace App\Conversions;

use App\Models\FileConversion;
use App\Services\LibreOffice;

class WordToPdfTool extends ConversionTool
{
    public function resultExtension(): string
    {
        return 'pdf';
    }

    public function process(FileConversion $conversion, string $directory): ToolResult
    {
        $this->engine = 'libreoffice';
        $pdf = app(LibreOffice::class)->convert($directory.'/'.basename($conversion->input_path), $directory, 'pdf', 'pdf');
        if (! rename($pdf, $directory.'/result.pdf')) {
            throw new \RuntimeException('No se pudo guardar el PDF.');
        }

        return new ToolResult($directory.'/result.pdf');
    }
}
