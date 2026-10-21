<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * Reads a PDF with Poppler before conversion: protection, damage, page count and which pages
 * have no selectable text (scanned), so mixed documents are not reported as fully editable.
 */
class PdfInspector
{
    private const MIN_PAGE_CHARACTERS = 20;

    /** @return array{pages: int, pages_without_text: list<int>} */
    public function inspect(string $path): array
    {
        $info = new Process([config('conversions.pdfinfo'), $path]);
        $info->setTimeout(60);
        $info->run();

        if (! $info->isSuccessful()) {
            throw new \InvalidArgumentException(str_contains($info->getErrorOutput(), 'Incorrect password')
                ? 'El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.'
                : 'El PDF está dañado o no se puede leer.');
        }

        preg_match('/^Pages:\s+(\d+)/m', $info->getOutput(), $match);
        $pages = (int) ($match[1] ?? 0);
        if ($pages === 0) {
            throw new \InvalidArgumentException('El PDF está dañado o no se puede leer.');
        }
        if ($pages > config('conversions.pdf_max_pages')) {
            throw new \InvalidArgumentException('El PDF tiene '.$pages.' páginas; el máximo es '.config('conversions.pdf_max_pages').'.');
        }

        $text = new Process([config('conversions.pdftotext'), '-enc', 'UTF-8', $path, '-']);
        $text->setTimeout(120);
        $text->run();

        if (! $text->isSuccessful()) {
            throw new \InvalidArgumentException('El PDF está dañado o no se puede leer.');
        }

        // pdftotext ends every page with a form feed.
        $characters = array_map(
            fn (string $page) => mb_strlen(preg_replace('/\s+/u', '', $page)),
            array_slice(explode("\f", $text->getOutput()), 0, $pages)
        );

        if (array_sum($characters) === 0) {
            return ['pages' => $pages, 'pages_without_text' => range(1, $pages)];
        }

        // A short page is only treated as scanned when it carries an image; short text pages stay as they are.
        $withImages = $this->pagesWithImages($path);
        $withoutText = [];
        foreach ($characters as $index => $count) {
            if ($count < self::MIN_PAGE_CHARACTERS && isset($withImages[$index + 1])) {
                $withoutText[] = $index + 1;
            }
        }

        return ['pages' => $pages, 'pages_without_text' => $withoutText];
    }

    /** @return array<int, true> */
    private function pagesWithImages(string $path): array
    {
        $images = new Process([config('conversions.pdfimages'), '-list', $path]);
        $images->setTimeout(60);
        $images->run();

        $pages = [];
        foreach (explode("\n", $images->getOutput()) as $line) {
            if (preg_match('/^\s*(\d+)\s+\d+\s+(image|mask|smask|stencil)\b/', $line, $match)) {
                $pages[(int) $match[1]] = true;
            }
        }

        return $pages;
    }
}
