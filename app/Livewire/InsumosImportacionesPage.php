<?php

namespace App\Livewire;

use App\Services\InsumoStockExcelImporter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
class InsumosImportacionesPage extends Component
{
    use WithFileUploads;

    public $archivo = null;

    #[Locked]
    public ?array $preview = null;

    public bool $confirmarCambios = false;

    public function updatedArchivo(): void
    {
        $this->reset('preview', 'confirmarCambios');
        $this->resetErrorBag('archivo');
    }

    public function previsualizar(InsumoStockExcelImporter $importer): void
    {
        Gate::authorize('insumos.importar');
        $this->validate(['archivo' => ['required', 'file', 'mimes:xls,xlsx', 'max:5120']]);

        try {
            $this->preview = $importer->preview($this->archivo->getRealPath());
            $this->confirmarCambios = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('archivo', $exception->getMessage());
        }
    }

    public function confirmar(InsumoStockExcelImporter $importer): void
    {
        Gate::authorize('insumos.importar');
        $this->validate(['archivo' => ['required', 'file', 'mimes:xls,xlsx', 'max:5120']]);
        $this->validate(['confirmarCambios' => ['accepted']]);

        if ($this->preview === null || $this->preview['archivo_hash'] !== hash_file('sha256', $this->archivo->getRealPath())) {
            $this->addError('archivo', 'Debe generar una previsualizacion antes de confirmar.');

            return;
        }

        try {
            $extension = $this->archivo->guessExtension() === 'xls' ? 'xls' : 'xlsx';
            $path = $this->archivo->storeAs('insumos/importaciones', "{$this->preview['archivo_hash']}.{$extension}", 'local');
            $result = $importer->importar(
                Storage::disk('local')->path($path),
                $this->archivo->getClientOriginalName(),
                auth()->id(),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('archivo', $exception->getMessage());

            return;
        }

        $this->dispatch(
            'app-toast',
            icon: $result['aplicada'] ? 'success' : 'info',
            message: $result['aplicada'] ? 'Stock importado correctamente.' : 'Este archivo ya fue importado.',
        );
        $this->reset('archivo', 'preview', 'confirmarCambios');
    }

    public function render()
    {
        Gate::authorize('insumos.importar');

        return view('livewire.insumos-importaciones-page');
    }
}
