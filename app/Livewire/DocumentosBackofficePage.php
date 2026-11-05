<?php

namespace App\Livewire;

use App\Models\AccesoDirecto;
use App\Services\BackofficeDocumentosCatalog;
use App\Services\BuscadorSentenciasExcelImporter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DocumentosBackofficePage extends Component
{
    private const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx'];

    use WithFileUploads;

    public string $procedimientoNombre = '';

    public ?int $procedimientoOrden = null;

    public $procedimientoArchivo;

    public string $documentoNombre = '';

    public ?int $documentoOrden = null;

    public $documentoArchivo;

    public string $tribunalNombre = '';

    public string $encargadoAudienciaNombre = '';

    public string $encargadoAudienciaNumeros = '';

    public $sentenciasExcel;

    public string $accesoGrupo = '';

    public string $accesoNombre = '';

    public string $accesoUrl = '';

    public ?int $editingAccesoId = null;

    public array $sectionsOpen = [
        'procedimientos' => false,
        'documentos' => false,
        'migracion' => false,
        'encargados' => false,
        'accesos' => false,
    ];

    public function mount(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.ver');

        $this->procedimientoOrden = count($catalog->procedimientos()) + 1;
        $this->documentoOrden = count($catalog->documentos()) + 1;
    }

    public function toggleSection(string $section): void
    {
        if (! array_key_exists($section, $this->sectionsOpen)) {
            return;
        }

        $this->sectionsOpen[$section] = ! $this->sectionsOpen[$section];
    }

    public function addProcedimiento(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'procedimientoNombre' => ['required', 'string', 'max:255'],
            'procedimientoOrden' => ['required', 'integer', 'min:1'],
            'procedimientoArchivo' => ['required', File::types(self::DOCUMENT_EXTENSIONS)->extensions(self::DOCUMENT_EXTENSIONS)->max(20 * 1024)],
        ]);

        $catalog->addDocumento(
            'procedimiento',
            $validated['procedimientoNombre'],
            $this->storeUploadedFile($this->procedimientoArchivo, 'procedimientos'),
            (int) $validated['procedimientoOrden'],
        );

        $this->reset(['procedimientoNombre', 'procedimientoArchivo']);
        $this->procedimientoOrden = count($catalog->procedimientos()) + 1;
        $this->dispatch('app-toast', message: 'Procedimiento agregado correctamente.');
    }

    public function removeProcedimiento(int $id, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $catalog->archiveDocumento($id);
        $this->dispatch('app-toast', message: 'Procedimiento archivado correctamente.');
    }

    public function addDocumento(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'documentoNombre' => ['required', 'string', 'max:255'],
            'documentoOrden' => ['required', 'integer', 'min:1'],
            'documentoArchivo' => ['required', File::types(self::DOCUMENT_EXTENSIONS)->extensions(self::DOCUMENT_EXTENSIONS)->max(20 * 1024)],
        ]);

        $catalog->addDocumento(
            'documento',
            $validated['documentoNombre'],
            $this->storeUploadedFile($this->documentoArchivo, 'doc'),
            (int) $validated['documentoOrden'],
        );

        $this->reset(['documentoNombre', 'documentoArchivo']);
        $this->documentoOrden = count($catalog->documentos()) + 1;
        $this->dispatch('app-toast', message: 'Documento agregado correctamente.');
    }

    public function removeDocumento(int $id, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $catalog->archiveDocumento($id);
        $this->dispatch('app-toast', message: 'Documento archivado correctamente.');
    }

    public function addTribunal(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'tribunalNombre' => ['required', 'string', 'max:255'],
        ]);

        $data = $catalog->all();
        $tribunales = Arr::get($data, 'tribunales', []);

        $tribunales[] = [
            'nombre' => trim($validated['tribunalNombre']),
            'activo' => true,
        ];

        $data['tribunales'] = $tribunales;
        $catalog->save($data);

        $this->reset('tribunalNombre');
        $this->dispatch('app-toast', message: 'Tribunal agregado correctamente.');
    }

    public function addEncargadoAudiencia(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'encargadoAudienciaNombre' => ['required', 'string', 'max:255'],
            'encargadoAudienciaNumeros' => ['required', 'string', 'max:50'],
        ]);

        $data = $catalog->all();
        $encargados = Arr::get($data, 'encargados_audiencia', []);

        $encargados[] = [
            'nombre' => trim($validated['encargadoAudienciaNombre']),
            'numeros' => trim($validated['encargadoAudienciaNumeros']),
            'activo' => true,
        ];

        $data['encargados_audiencia'] = $encargados;
        $catalog->save($data);

        $this->reset(['encargadoAudienciaNombre', 'encargadoAudienciaNumeros']);
        $this->dispatch('app-toast', message: 'Encargado de audiencia agregado correctamente.');
    }

    public function removeTribunal(int $index, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $data = $catalog->all();
        $tribunales = Arr::get($data, 'tribunales', []);

        if (! array_key_exists($index, $tribunales)) {
            return;
        }

        unset($tribunales[$index]);
        $data['tribunales'] = array_values($tribunales);
        $catalog->save($data);
        $this->dispatch('app-toast', message: 'Tribunal quitado del listado.');
    }

    public function removeEncargadoAudiencia(int $index, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $data = $catalog->all();
        $encargados = Arr::get($data, 'encargados_audiencia', []);

        if (! array_key_exists($index, $encargados)) {
            return;
        }

        unset($encargados[$index]);
        $data['encargados_audiencia'] = array_values($encargados);
        $catalog->save($data);
        $this->dispatch('app-toast', message: 'Encargado de audiencia quitado del listado.');
    }

    public function saveAcceso(): void
    {
        Gate::authorize('documentos.gestionar');

        // Solo http(s): la URL se publica en la página pública sin login
        $validated = $this->validate([
            'accesoGrupo' => ['required', 'string', 'max:80'],
            'accesoNombre' => ['required', 'string', 'max:120'],
            'accesoUrl' => ['required', 'string', 'max:500', 'url:http,https'],
        ], [
            'accesoUrl.url' => 'La URL debe empezar con http:// o https://.',
        ]);

        $datos = [
            'grupo' => trim($validated['accesoGrupo']),
            'nombre' => trim($validated['accesoNombre']),
            'url' => trim($validated['accesoUrl']),
        ];

        if ($this->editingAccesoId) {
            AccesoDirecto::findOrFail($this->editingAccesoId)->update($datos);
            $mensaje = 'Acceso actualizado correctamente.';
        } else {
            AccesoDirecto::create([...$datos, 'orden' => (int) AccesoDirecto::max('orden') + 1, 'activo' => true]);
            $mensaje = 'Acceso agregado correctamente.';
        }

        $this->cancelAcceso();
        $this->dispatch('app-toast', message: $mensaje);
    }

    public function editAcceso(int $id): void
    {
        Gate::authorize('documentos.gestionar');

        $acceso = AccesoDirecto::findOrFail($id);
        $this->editingAccesoId = $acceso->id;
        $this->accesoGrupo = $acceso->grupo;
        $this->accesoNombre = $acceso->nombre;
        $this->accesoUrl = $acceso->url;
        $this->resetValidation();
    }

    public function cancelAcceso(): void
    {
        $this->reset(['accesoGrupo', 'accesoNombre', 'accesoUrl', 'editingAccesoId']);
        $this->resetValidation();
    }

    public function toggleAcceso(int $id): void
    {
        Gate::authorize('documentos.gestionar');

        $acceso = AccesoDirecto::findOrFail($id);
        $acceso->update(['activo' => ! $acceso->activo]);
        $this->dispatch('app-toast', message: $acceso->activo ? 'Acceso visible en la página pública.' : 'Acceso oculto de la página pública.');
    }

    public function moveAcceso(int $id, int $direction): void
    {
        Gate::authorize('documentos.gestionar');

        $accesos = AccesoDirecto::query()->ordenados()->get()->values();
        $from = $accesos->search(fn (AccesoDirecto $acceso) => $acceso->id === $id);
        $to = $from === false ? false : $from + ($direction < 0 ? -1 : 1);

        if ($to === false || ! $accesos->has($to)) {
            return;
        }

        // Reasigna el orden completo para que quede correlativo aunque hubiera huecos
        $ordenados = $accesos->all();
        [$ordenados[$from], $ordenados[$to]] = [$ordenados[$to], $ordenados[$from]];
        foreach ($ordenados as $i => $acceso) {
            $acceso->update(['orden' => $i + 1]);
        }
    }

    public function removeAcceso(int $id): void
    {
        Gate::authorize('documentos.gestionar');

        AccesoDirecto::findOrFail($id)->delete();

        if ($this->editingAccesoId === $id) {
            $this->cancelAcceso();
        }

        $this->dispatch('app-toast', message: 'Acceso eliminado.');
    }

    public function importSentencias(BuscadorSentenciasExcelImporter $importer): void
    {
        Gate::authorize('sentencias.importar');

        $this->validate([
            'sentenciasExcel' => ['required', File::types(['xlsx'])->max(30 * 1024)],
        ]);

        $result = $importer->import($this->sentenciasExcel->getRealPath());

        $this->reset('sentenciasExcel');

        $this->dispatch('app-toast', message: 'Importación de sentencias completada. Filas válidas: '.$result['total_filas_validas'].'. Filas insertadas: '.$result['filas_insertadas'].'. Filas duplicadas: '.($result['filas_duplicadas'] ?? 0).'. Filas omitidas: '.($result['filas_omitidas'] ?? 0).'.');
    }

    public function render(BackofficeDocumentosCatalog $catalog)
    {
        Gate::authorize('documentos.ver');

        $data = $catalog->all();

        return view('livewire.documentos-backoffice-page', [
            'procedimientos' => Arr::get($data, 'procedimientos', []),
            'documentos' => Arr::get($data, 'documentos', []),
            'tribunales' => Arr::get($data, 'tribunales', []),
            'encargadosAudiencia' => Arr::get($data, 'encargados_audiencia', []),
            'accesos' => AccesoDirecto::query()->ordenados()->get(),
            'updatedAt' => Arr::get($data, 'meta.updated_at'),
        ]);
    }

    protected function storeUploadedFile(mixed $file, string $section): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $this->safeDocumentExtension($file);
        $slug = Str::slug($originalName);
        $slug = $slug !== '' ? $slug : 'archivo';

        $filename = $slug.'-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$extension;
        $storedPath = $file->storeAs('documentos/'.$section, $filename, 'public');

        return $storedPath;
    }

    /**
     * La extensión guardada en el disco público debe coincidir con el tipo validado:
     * nunca se confía en el nombre que manda el navegador (evita servir .html/.svg).
     */
    protected function safeDocumentExtension(mixed $file): string
    {
        $client = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($client, self::DOCUMENT_EXTENSIONS, true)) {
            return $client;
        }

        $guessed = strtolower((string) $file->guessExtension());

        if (in_array($guessed, self::DOCUMENT_EXTENSIONS, true)) {
            return $guessed;
        }

        throw ValidationException::withMessages([
            'archivo' => 'El archivo debe ser PDF, DOC o DOCX.',
        ]);
    }
}
