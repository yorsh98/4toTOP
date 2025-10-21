<?php

namespace App\Livewire;

use App\Models\Oficio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class OficioTabla extends Component
{
    use WithPagination;

    // ===== UI / estado =====
    public string $search = '';
    public string $filtroAnio = '';
    public int $perPage = 10;
    public string $sortField = 'id';
    public string $sortDirection = 'desc';

    /**
     * Modo de la tabla:
     *  - 'full'  => CRUD habilitado (crear, editar, eliminar, restaurar)
     *  - 'vista' => solo lectura (sin mutaciones solo  ver eliminados)
     */
    public string $modo = 'full';

    public bool $showCreateForm = false;

    // ===== Crear =====
    public string $newCausaAsig = '';
    public string $newUserSolicitante = '';
    public string $newUserDirigido = '';
    public ?string $newAnio = null; // opcional

    // ===== Editar =====
    public ?int $editingId = null;
    public string $editCausaAsig = '';
    public string $editUserSolicitante = '';
    public string $editUserDirigido = '';
    public string $editAnio = '';
    public string $editNumEntregado = '';

    // ===== Eliminados (soft) toggle =====
    public bool $verEliminados = false;

    protected $paginationTheme = 'bootstrap';

    // ------ Ciclo de vida ------
    public function mount(string $modo = 'full'): void
    {
        $this->modo = in_array($modo, ['full','vista'], true) ? $modo : 'full';

        // En modo vista, fuerza estado de solo lectura
        if ($this->isVista()) {
            $this->showCreateForm = false;
            //$this->verEliminados  = false; // no mostrar botón ni listado de eliminados en vista, con esto no muestra boton de eliminados en modo "vista"
        }
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'filtroAnio', 'perPage'])) {
            $this->resetPage();
        }
    }

    // ------ Helpers de modo ------
    public function isFull(): bool  { return $this->modo === 'full'; }
    public function isVista(): bool { return $this->modo === 'vista'; }

    /** Retorna true si se debe abortar por solo-lectura */
    private function guardReadOnly(): bool
    {
        if ($this->isVista()) {
            // En "vista" no se permiten mutaciones
            session()->flash('error', 'Operación no permitida en modo vista.');
            // Opcional: emitir alerta SweetAlert de error (si ya la consideras)
            $this->dispatch('alerta-error');
            return true;
        }
        return false;
    }

    // ------ Ordenamiento ------
    public function sortBy(string $field): void
    {
        // Sorting sí se permite en ambos modos
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // ------ Crear ------
    public function resetCreateForm(): void
    {
        $this->newCausaAsig = '';
        $this->newUserSolicitante = '';
        $this->newUserDirigido = '';
        $this->newAnio = null;
    }

    public function create(): void
    {
        if ($this->guardReadOnly()) return;

        $this->validate([
            'newCausaAsig'       => ['required','regex:/^[a-zA-Z0-9\s\-]{1,10}$/'],
            'newUserSolicitante' => ['required','string','max:255'],
            'newUserDirigido'    => ['required','regex:/^[a-zA-Z0-9\s\-]+$/'],
            'newAnio'            => ['nullable','digits:4'],
        ], [
            'newCausaAsig.regex'    => 'CAUSA ASIGNADA permite letras/números/espacios/guión, máx 10.',
            'newUserDirigido.regex' => 'MOTIVO permite letras/números/espacios/guión.',
        ]);

        $anio = (int)($this->newAnio ?: date('Y'));

        // Transacción: reutiliza número soft-deleted del año, si no hay => correlativo max+1
        $lib = DB::transaction(function () use ($anio) {
            $candidato = Oficio::onlyTrashed()
                ->where('año', $anio)
                ->orderBy('deleted_at', 'asc')
                ->lockForUpdate()
                ->first();

            if ($candidato) {
                $candidato->restore();
                $candidato->fill([
                    'CausaAsig'       => $this->newCausaAsig,
                    'UserSolicitante' => $this->newUserSolicitante,
                    'UserDirigido'    => $this->newUserDirigido,
                ])->save();
                return $candidato;
            }

            $max = Oficio::whereNull('deleted_at')->where('año', $anio)->max('Numentregado');

            $nuevo = new Oficio();
            $nuevo->Numentregado    = $max ? $max + 1 : 1;
            $nuevo->año             = $anio;
            $nuevo->CausaAsig       = $this->newCausaAsig;
            $nuevo->UserSolicitante = $this->newUserSolicitante;
            $nuevo->UserDirigido    = $this->newUserDirigido;
            $nuevo->save();

            return $nuevo;
        }, 3);

        $this->resetCreateForm();
        $this->showCreateForm = false;
        $this->dispatch('alerta-exito'); // SweetAlert
        session()->flash('success', 'Oficio creado. N° '.$lib->Numentregado.' / '.$lib->año);
    }

    // ------ Editar / Actualizar ------
    public function edit(int $id): void
    {
        if ($this->guardReadOnly()) return;

        $lib = Oficio::withTrashed()->findOrFail($id);
        $this->editingId = $lib->id;
        $this->editCausaAsig = $lib->CausaAsig;
        $this->editUserSolicitante = $lib->UserSolicitante;
        $this->editUserDirigido = $lib->UserDirigido;
        $this->editAnio = (string)$lib->año;
        $this->editNumEntregado = (string)$lib->Numentregado;

        $this->dispatch('open-edit-modal'); // Alpine
    }

    public function update(): void
    {
        if ($this->guardReadOnly()) return;
        if (!$this->editingId) return;

        $this->validate([
            'editCausaAsig'       => ['required','regex:/^[a-zA-Z0-9\s\-]{1,10}$/'],
            'editUserSolicitante' => ['required','string','max:255'],
            'editUserDirigido'    => ['required','regex:/^[a-zA-Z0-9\s\-]+$/'],
            'editAnio'            => ['required','digits:4'],
            'editNumEntregado'    => ['required','integer','between:1,99999'],
        ]);

        $lib = Oficio::withTrashed()->findOrFail($this->editingId);

        // Unicidad activa: (Numentregado, año, deleted_at IS NULL)
        $existeActivo = Oficio::where('id', '!=', $lib->id)
            ->whereNull('deleted_at')
            ->where('año', (int)$this->editAnio)
            ->where('Numentregado', (int)$this->editNumEntregado)
            ->exists();

        if ($existeActivo) {
            $this->addError('editNumEntregado', 'Ya existe un activo con ese N° en ese año.');
            return;
        }

        $lib->fill([
            'CausaAsig'       => $this->editCausaAsig,
            'UserSolicitante' => $this->editUserSolicitante,
            'UserDirigido'    => $this->editUserDirigido,
            'año'             => (int)$this->editAnio,
            'Numentregado'    => (int)$this->editNumEntregado,
        ])->save();

        $this->dispatch('close-edit-modal');
        $this->dispatch('alerta-exito');
        session()->flash('success', 'Oficio actualizado');
    }

    // ------ Eliminar / Restaurar ------
    public function confirmDelete(int $id): void
    {
        if ($this->guardReadOnly()) return;
        $this->delete($id);
    }

    public function delete(int $id): void
    {
        if ($this->guardReadOnly()) return;

        $lib = Oficio::find($id);
        if (!$lib) return;

        $lib->delete(); // Soft delete
        $this->dispatch('alerta-deleted');
        session()->flash('success', 'Eliminada (soft). El número quedó disponible.');
    }

    public function restore(int $id): void
    {
        if ($this->guardReadOnly()) return;

        $lib = Oficio::onlyTrashed()->find($id);
        if (!$lib) return;

        $colision = Oficio::whereNull('deleted_at')
            ->where('año', $lib->año)
            ->where('Numentregado', $lib->Numentregado)
            ->exists();

        if ($colision) {
            session()->flash('error', 'No se puede restaurar: ya existe un activo con ese N°/año.');
            return;
        }

        $lib->restore();
        session()->flash('success', 'Restaurada correctamente.');
    }

    public function toggleEliminados(): void
{
    // En vista también se permite ver/ocultar eliminados
    $this->verEliminados = ! $this->verEliminados;
    $this->resetPage();
}


    // ------ Render ------
    public function render()
    {
        $q = $this->queryBase();

        if ($this->verEliminados) {
            $q->onlyTrashed();
        }

        $Oficios = $q
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Años para filtro (en "vista" no mostramos eliminados)
        $anios = Oficio::query()
            ->select('año')
            ->when($this->isFull() && $this->verEliminados, fn($qq) => $qq->onlyTrashed())
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->pluck('año')
            ->toArray();

        return view('livewire.oficio-tabla', [
            'oficios'    => $Oficios,
            'anios'      => $anios,
        ]);
    }

    protected function queryBase(): Builder
    {
        return Oficio::query()
            ->when(!$this->verEliminados, fn($qq) => $qq->whereNull('deleted_at'))
            ->when($this->filtroAnio !== '', fn($qq) => $qq->where('año', (int)$this->filtroAnio))
            ->when($this->search !== '', function ($qq) {
                $s = trim($this->search);
                $qq->where(function ($w) use ($s) {
                    $w->where('CausaAsig', 'like', "%{$s}%")
                      ->orWhere('UserSolicitante', 'like', "%{$s}%")
                      ->orWhere('UserDirigido', 'like', "%{$s}%")
                      ->orWhere('Numentregado', 'like', "%{$s}%")
                      ->orWhere('año', 'like', "%{$s}%");
                });
            });
    }
}
