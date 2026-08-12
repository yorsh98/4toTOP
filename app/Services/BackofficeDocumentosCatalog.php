<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\DocumentoMovimiento;
use Illuminate\Support\Arr;

class BackofficeDocumentosCatalog
{
    public function all(): array
    {
        return $this->load();
    }

    public function procedimientos(): array
    {
        return $this->documentosByCategory('procedimiento');
    }

    public function procedimientosPublicos(): array
    {
        return $this->filterPublicos($this->procedimientos());
    }

    public function documentos(): array
    {
        return $this->documentosByCategory('documento');
    }

    public function documentosPublicos(): array
    {
        return $this->filterPublicos($this->documentos());
    }

    public function tribunales(): array
    {
        return $this->filterActivos($this->loadSection('tribunales'));
    }

    public function encargadosAudiencia(): array
    {
        return $this->filterActivos($this->loadSection('encargados_audiencia'));
    }

    public function save(array $catalog): void
    {
        $normalized = [
            // Documents belong in the database; these values only preserve the legacy file format.
            'procedimientos' => $this->normalizeDocumentSection(Arr::get($catalog, 'procedimientos', [])),
            'documentos' => $this->normalizeDocumentSection(Arr::get($catalog, 'documentos', [])),
            'encargados_audiencia' => $this->normalizeEncargadosAudiencia(Arr::get($catalog, 'encargados_audiencia', [])),
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
            'procedimientos' => $this->documentosByCategory('procedimiento'),
            'documentos' => $this->documentosByCategory('documento'),
            'encargados_audiencia' => $this->normalizeEncargadosAudiencia(
                Arr::get($decoded, 'encargados_audiencia', Arr::get(config('backoffice_documentos', []), 'encargados_audiencia', []))
            ),
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
            'encargados_audiencia' => Arr::get($defaults, 'encargados_audiencia', []),
            'tribunales' => Arr::get($defaults, 'tribunales', []),
        ]);
    }

    public function addDocumento(string $categoria, string $nombre, string $archivo, int $orden): Documento
    {
        $documento = Documento::create([
            'categoria' => $categoria,
            'nombre' => trim($nombre),
            'archivo' => $archivo,
            'orden' => $orden,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->recordMovement($documento, 'creado', ['archivo' => $archivo]);

        return $documento;
    }

    public function archiveDocumento(int $id): void
    {
        $documento = Documento::findOrFail($id);
        $documento->update(['activo' => false, 'updated_by' => auth()->id()]);
        $documento->delete();

        $this->recordMovement($documento, 'archivado');
    }

    protected function documentosByCategory(string $categoria): array
    {
        return Documento::query()
            ->where('categoria', $categoria)
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (Documento $documento) => [
                'id' => $documento->id,
                'nombre' => $documento->nombre,
                'url' => $documento->url(),
                'orden' => $documento->orden,
                'activo' => $documento->activo,
                'publico' => $documento->publico,
                'created_at' => $documento->created_at?->format('d-m-Y H:i'),
            ])
            ->all();
    }

    protected function recordMovement(Documento $documento, string $accion, ?array $detalle = null): void
    {
        DocumentoMovimiento::create([
            'documento_id' => $documento->id,
            'user_id' => auth()->id(),
            'accion' => $accion,
            'detalle' => $detalle,
            'created_at' => now(),
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
                    'publico' => (bool) Arr::get($row, 'publico', true),
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

    protected function normalizeEncargadosAudiencia(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && trim((string) Arr::get($row, 'nombre', '')) !== '')
            ->values()
            ->map(function (array $row): array {
                return [
                    'nombre' => trim((string) Arr::get($row, 'nombre', '')),
                    'numeros' => trim((string) Arr::get($row, 'numeros', '')),
                    'activo' => (bool) Arr::get($row, 'activo', true),
                ];
            })
            ->values()
            ->all();
    }

    protected function filterActivos(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => (bool) Arr::get($row, 'activo', true))
            ->values()
            ->all();
    }

    protected function filterPublicos(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => (bool) Arr::get($row, 'publico', true))
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
