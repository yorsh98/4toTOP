<?php

namespace App\Livewire;

use App\Services\BackofficeDocumentosCatalog;
use App\Services\BuscadorSentenciasExcelImporter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DocumentosBackofficePage extends Component
{
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

    public function mount(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.ver');

        $data = $catalog->all();

        $this->procedimientoOrden = count(Arr::get($data, 'procedimientos', [])) + 1;
        $this->documentoOrden = count(Arr::get($data, 'documentos', [])) + 1;
    }

    public function addProcedimiento(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'procedimientoNombre' => ['required', 'string', 'max:255'],
            'procedimientoOrden' => ['required', 'integer', 'min:1'],
            'procedimientoArchivo' => ['required', File::types(['pdf', 'doc', 'docx'])->max(20 * 1024)],
        ]);

        $data = $catalog->all();
        $procedimientos = Arr::get($data, 'procedimientos', []);

        $procedimientos[] = [
            'nombre' => trim($validated['procedimientoNombre']),
            'url' => $this->storeUploadedFile($this->procedimientoArchivo, 'procedimientos'),
            'orden' => (int) $validated['procedimientoOrden'],
            'activo' => true,
        ];

        $data['procedimientos'] = $procedimientos;
        $catalog->save($data);

        $this->reset(['procedimientoNombre', 'procedimientoArchivo']);
        $this->procedimientoOrden = count($catalog->all()['procedimientos']) + 1;
        session()->flash('ok', 'Procedimiento agregado correctamente.');
    }

    public function removeProcedimiento(int $index, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $data = $catalog->all();
        $procedimientos = Arr::get($data, 'procedimientos', []);

        if (! array_key_exists($index, $procedimientos)) {
            return;
        }

        unset($procedimientos[$index]);
        $data['procedimientos'] = array_values($procedimientos);
        $catalog->save($data);
        session()->flash('ok', 'Procedimiento quitado del listado.');
    }

    public function addDocumento(BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $validated = $this->validate([
            'documentoNombre' => ['required', 'string', 'max:255'],
            'documentoOrden' => ['required', 'integer', 'min:1'],
            'documentoArchivo' => ['required', File::types(['pdf', 'doc', 'docx'])->max(20 * 1024)],
        ]);

        $data = $catalog->all();
        $documentos = Arr::get($data, 'documentos', []);

        $documentos[] = [
            'nombre' => trim($validated['documentoNombre']),
            'url' => $this->storeUploadedFile($this->documentoArchivo, 'doc'),
            'orden' => (int) $validated['documentoOrden'],
            'activo' => true,
        ];

        $data['documentos'] = $documentos;
        $catalog->save($data);

        $this->reset(['documentoNombre', 'documentoArchivo']);
        $this->documentoOrden = count($catalog->all()['documentos']) + 1;
        session()->flash('ok', 'Documento agregado correctamente.');
    }

    public function removeDocumento(int $index, BackofficeDocumentosCatalog $catalog): void
    {
        Gate::authorize('documentos.gestionar');

        $data = $catalog->all();
        $documentos = Arr::get($data, 'documentos', []);

        if (! array_key_exists($index, $documentos)) {
            return;
        }

        unset($documentos[$index]);
        $data['documentos'] = array_values($documentos);
        $catalog->save($data);
        session()->flash('ok', 'Documento quitado del listado.');
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
        session()->flash('ok', 'Tribunal agregado correctamente.');
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
        session()->flash('ok', 'Encargado de audiencia agregado correctamente.');
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
        session()->flash('ok', 'Tribunal quitado del listado.');
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
        session()->flash('ok', 'Encargado de audiencia quitado del listado.');
    }

    public function importSentencias(BuscadorSentenciasExcelImporter $importer): void
    {
        Gate::authorize('sentencias.importar');

        $this->validate([
            'sentenciasExcel' => ['required', File::types(['xlsx'])->max(30 * 1024)],
        ]);

        $result = $importer->import($this->sentenciasExcel->getRealPath());

        $this->reset('sentenciasExcel');

        session()->flash(
            'ok',
            'Importacion de sentencias completada. Filas validas: '.$result['total_filas_validas'].'. Filas insertadas: '.$result['filas_insertadas'].'. Filas duplicadas: '.($result['filas_duplicadas'] ?? 0).'. Filas omitidas: '.($result['filas_omitidas'] ?? 0).'.'
        );
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
            'updatedAt' => Arr::get($data, 'meta.updated_at'),
        ]);
    }

    protected function storeUploadedFile(mixed $file, string $section): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $slug = Str::slug($originalName);
        $slug = $slug !== '' ? $slug : 'archivo';

        $filename = $slug.'-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$extension;
        $storedPath = $file->storeAs('documentos/'.$section, $filename, 'public');

        return '/storage/'.$storedPath;
    }
}
