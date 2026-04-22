<?php

namespace App\Services;

use Illuminate\Support\Arr;

class BackofficeDocumentosCatalog
{
    public function all(): array
    {
        return $this->load();
    }

    public function procedimientos(): array
    {
        return $this->filterActivos($this->loadSection('procedimientos'));
    }

    public function documentos(): array
    {
        return $this->filterActivos($this->loadSection('documentos'));
    }

    public function tribunales(): array
    {
        return $this->filterActivos($this->loadSection('tribunales'));
    }

    public function save(array $catalog): void
    {
        $normalized = [
            'procedimientos' => $this->normalizeDocumentSection(Arr::get($catalog, 'procedimientos', [])),
            'documentos' => $this->normalizeDocumentSection(Arr::get($catalog, 'documentos', [])),
            'tribunales' => $this->normalizeTribunales(Arr::get($catalog, 'tribunales', [])),
            'meta' => [
                'updated_at' => now()->toDateTimeString(),
            ],
        ];

        $this->ensureDirectory();
        file_put_contents($this->filePath(), json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    protected function loadSection(string $key): array
    {
        return Arr::get($this->load(), $key, []);
    }

    protected function load(): array
    {
        if (! file_exists($this->filePath())) {
            $this->bootstrap();
        }

        $content = @file_get_contents($this->filePath());

        if (! is_string($content) || trim($content) === '') {
            $this->bootstrap();
            $content = (string) file_get_contents($this->filePath());
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            $this->bootstrap();
            $decoded = json_decode((string) file_get_contents($this->filePath()), true);
        }

        return [
            'procedimientos' => $this->normalizeDocumentSection(Arr::get($decoded, 'procedimientos', [])),
            'documentos' => $this->normalizeDocumentSection(Arr::get($decoded, 'documentos', [])),
            'tribunales' => $this->normalizeTribunales(Arr::get($decoded, 'tribunales', [])),
            'meta' => Arr::get($decoded, 'meta', []),
        ];
    }

    protected function bootstrap(): void
    {
        $defaults = config('backoffice_documentos', []);

        $this->save([
            'procedimientos' => Arr::get($defaults, 'procedimientos', []),
            'documentos' => Arr::get($defaults, 'documentos', []),
            'tribunales' => Arr::get($defaults, 'tribunales', []),
        ]);
    }

    protected function normalizeDocumentSection(array $rows): array
    {
        $normalized = collect($rows)
            ->filter(fn ($row) => is_array($row) && trim((string) Arr::get($row, 'nombre', '')) !== '')
            ->values()
            ->map(function (array $row, int $index): array {
                return [
                    'nombre' => trim((string) Arr::get($row, 'nombre', '')),
                    'url' => $this->normalizeUrl((string) Arr::get($row, 'url', '')),
                    'orden' => max(1, (int) Arr::get($row, 'orden', $index + 1)),
                    'activo' => (bool) Arr::get($row, 'activo', true),
                ];
            })
            ->sortBy('orden')
            ->values()
            ->all();

        return $normalized;
    }

    protected function normalizeTribunales(array $rows): array
    {
        $normalized = collect($rows)
            ->filter(fn ($row) => is_array($row) && trim((string) Arr::get($row, 'nombre', '')) !== '')
            ->values()
            ->map(function (array $row): array {
                return [
                    'nombre' => trim((string) Arr::get($row, 'nombre', '')),
                    'activo' => (bool) Arr::get($row, 'activo', true),
                ];
            })
            ->values()
            ->all();

        return $normalized;
    }

    protected function filterActivos(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => (bool) Arr::get($row, 'activo', true))
            ->values()
            ->all();
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return $url;
    }

    protected function ensureDirectory(): void
    {
        $directory = dirname($this->filePath());

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function filePath(): string
    {
        return storage_path('app/backoffice/documentos_catalogo.json');
    }
}
