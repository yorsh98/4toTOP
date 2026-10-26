<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * Page operations with PyMuPDF (scripts/pdf_pages.py): merge, split (with the modes of iLovePDF: custom
 * ranges, fixed ranges of N pages, extract every page or selected pages), bake, rotate, organize, number
 * pages and images to PDF. Signature fields and annotations are baked into the page so they survive.
 * Every method that rewrites a PDF returns how many annotations and fields were baked.
 */
class PdfPageTools
{
    public const SPLIT_MODES = ['custom', 'fixed', 'extract_all', 'extract_select'];

    /**
     * Parses "1-3, 5, 8-10" into [[1, 3], [5, 5], [8, 10]], keeping the order the user wrote.
     *
     * @return list<array{int, int}>
     */
    public static function parseRanges(string $spec, int $pages): array
    {
        $ranges = [];
        foreach (preg_split('/[,;]+/', $spec) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (! preg_match('/^(\d+)\s*(?:-\s*(\d+))?$/', $part, $match)) {
                throw new \InvalidArgumentException("«{$part}» no es un rango válido. Usa el formato 1-3, 5, 8-10.");
            }
            $from = (int) $match[1];
            $to = isset($match[2]) ? (int) $match[2] : $from;
            if ($from < 1 || $to < $from) {
                throw new \InvalidArgumentException("El rango «{$part}» no es válido: la página inicial debe ser 1 o más y no superar a la final.");
            }
            if ($to > $pages) {
                throw new \InvalidArgumentException("El rango «{$part}» supera las {$pages} páginas del PDF.");
            }
            $ranges[] = [$from, $to];
        }

        if ($ranges === []) {
            throw new \InvalidArgumentException('Indica al menos una página o rango.');
        }

        return $ranges;
    }

    /**
     * The groups of pages each split mode produces; each group becomes one PDF.
     *
     * @param  array{mode: string, ranges?: string, every?: int, pages?: string, merge?: bool}  $options
     * @return list<list<array{int, int}>>
     */
    public static function splitGroups(array $options, int $pages): array
    {
        return match ($options['mode'] ?? null) {
            'custom' => self::grouped(self::parseRanges($options['ranges'] ?? '', $pages), (bool) ($options['merge'] ?? false)),
            'fixed' => self::fixedGroups((int) ($options['every'] ?? 0), $pages),
            'extract_all' => array_map(fn (int $page) => [[$page, $page]], range(1, $pages)),
            'extract_select' => self::grouped(self::pagesOf(self::parseRanges($options['pages'] ?? '', $pages)), (bool) ($options['merge'] ?? false)),
            default => throw new \InvalidArgumentException('Modo de división desconocido.'),
        };
    }

    /**
     * @param  list<string>  $inputs
     * @return int annotations and signature fields baked into the pages
     */
    public function merge(array $inputs, string $output): int
    {
        return $this->run(['merge', $output, ...$inputs]);
    }

    /**
     * Writes one PDF per group into $directory and returns them as file name => path, in order,
     * with the number of annotations and signature fields baked into the pages.
     *
     * @param  list<list<array{int, int}>>  $groups
     * @return array{array<string, string>, int}
     */
    public function split(string $input, array $groups, string $directory, string $baseName): array
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            throw new \RuntimeException('No se pudo preparar la división del PDF.');
        }

        $parts = [];
        $plan = [];
        foreach ($groups as $group) {
            $label = implode('_', array_map(fn (array $range) => $range[0] === $range[1] ? (string) $range[0] : $range[0].'-'.$range[1], $group));
            $name = $baseName.'_'.(strlen($label) > 60 ? 'parte-'.(count($parts) + 1) : $label).'.pdf';
            $parts[$name] = $directory.'/'.$name;
            $plan[] = ['path' => $parts[$name], 'ranges' => $group];
        }

        return [$parts, $this->run(['split', $input], $plan)];
    }

    /** Writes $input with its annotations and signature fields baked into the pages. */
    public function bake(string $input, string $output): int
    {
        return $this->run(['bake', $input, $output]);
    }

    /** @param list<int> $pages */
    public function rotate(string $input, string $output, array $pages, int $angle): int
    {
        return $this->run(['rotate', $input, $output], ['pages' => $pages, 'angle' => $angle]);
    }

    /** @param list<array{page: int, rotation: int}> $plan the pages to keep, in their new order */
    public function organize(string $input, string $output, array $plan): int
    {
        return $this->run(['organize', $input, $output], $plan);
    }

    /** @param array{position: string, margin: string, format: string, start: int, first: int, last: int, size: int} $spec */
    public function number(string $input, string $output, array $spec): int
    {
        return $this->run(['number', $input, $output], $spec);
    }

    /** @param array{parts: list<array{path: string, images: list<string>}>, page_size: string, orientation: string, margin: string} $spec */
    public function images(array $spec): void
    {
        $this->run(['images'], $spec);
    }

    /** @param list<string> $arguments */
    private function run(array $arguments, ?array $stdin = null): int
    {
        $process = new Process([config('conversions.python'), '-I', base_path('scripts/pdf_pages.py'), ...$arguments]);
        $process->setInput($stdin === null ? null : json_encode($stdin, JSON_THROW_ON_ERROR));
        $process->setTimeout(300);
        $process->run();

        if ($process->getExitCode() === 3) {
            throw new \InvalidArgumentException('El PDF está protegido con contraseña. Quita la protección e inténtalo de nuevo.');
        }
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('pdf_pages.py: '.mb_substr($process->getErrorOutput(), -500));
        }

        return (int) (json_decode($process->getOutput(), true)['flattened'] ?? 0);
    }

    /**
     * @param  list<array{int, int}>  $ranges
     * @return list<list<array{int, int}>>
     */
    private static function grouped(array $ranges, bool $merge): array
    {
        return $merge ? [$ranges] : array_map(fn (array $range) => [$range], $ranges);
    }

    /** @return list<list<array{int, int}>> */
    private static function fixedGroups(int $every, int $pages): array
    {
        if ($every < 1) {
            throw new \InvalidArgumentException('Indica cada cuántas páginas dividir el PDF.');
        }

        return array_map(fn (int $from) => [[$from, min($from + $every - 1, $pages)]], range(1, $pages, $every));
    }

    /**
     * Expands ranges into single-page ranges, so "1-3" becomes [1,1], [2,2], [3,3].
     *
     * @param  list<array{int, int}>  $ranges
     * @return list<array{int, int}>
     */
    private static function pagesOf(array $ranges): array
    {
        return array_merge(...array_map(fn (array $range) => array_map(fn (int $page) => [$page, $page], range($range[0], $range[1])), $ranges));
    }
}
