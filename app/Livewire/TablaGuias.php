<?php

namespace App\Livewire;

use App\Models\Guias;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaGuias extends Component
{
    use WithPagination;

    // Estado de servidor: el cliente no puede modificarlo desde /livewire/update.
    #[Locked]
    public $modo = 'full'; // 'full' o 'lectura'

    public $search = '';

    public $perPage = 10;

    #[Locked]
    public $sortField = 'nombre_completo';

    #[Locked]
    public $sortDirection = 'asc';

    public $filtroInstitucion = '';

    // Campos para edición
    #[Locked]
    public $editId;

    public $editNombre;

    public $editRut;

    public $editEmail;

    public $editTelefono1;

    public $editTelefono2;

    public $editInstitucion;

    // Campos para creación
    public $showCreateForm = false;

    public $newNombre;

    public $newRut;

    public $newEmail;

    public $newTelefono1;

    public $newTelefono2;

    public $newInstitucion;

    protected $rules = [
        'editNombre' => 'required|min:3|max:100',
        'editRut' => 'nullable|max:20',
        'editEmail' => 'nullable|email|max:100',
        'editTelefono1' => 'nullable|max:20',
        'editTelefono2' => 'nullable|max:20',
        'editInstitucion' => 'required|numeric|between:1,11',

        'newNombre' => 'required|min:3|max:100',
        'newRut' => 'nullable|max:20',
        'newEmail' => 'nullable|email|max:100',
        'newTelefono1' => 'nullable|max:20',
        'newTelefono2' => 'nullable|max:20',
        'newInstitucion' => 'required|numeric|between:1,11',
    ];

    protected $messages = [
        'required' => 'El campo :attribute es obligatorio.',
        'email' => 'El campo :attribute debe ser un email válido.',
        'numeric' => 'El campo :attribute debe ser un número.',
        'between' => 'El campo :attribute debe estar entre :min y :max.',
        'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        'max' => 'El campo :attribute no debe exceder :max caracteres.',
    ];

    protected $paginationTheme = 'bootstrap';

    private const SORTABLE = ['nombre_completo', 'rut', 'email', 'telefono1', 'telefono2', 'institucion'];

    private const PER_PAGE = [10, 25, 50, 100];

    private function authorizeRead(): void
    {
        // Falla cerrado: solo el modo público 'lectura' se salta el permiso.
        if ($this->modo !== 'lectura') {
            Gate::authorize('guias.ver');
        }
    }

    private function sortableFields(): array
    {
        return $this->modo === 'lectura'
            ? array_values(array_diff(self::SORTABLE, ['rut']))
            : self::SORTABLE;
    }

    private function authorizeWrite(): void
    {
        abort_unless($this->modo === 'full', 403, 'No autorizado.');
        Gate::authorize('guias.editar');
    }

    public function sortBy($campo)
    {
        if (! is_string($campo) || ! in_array($campo, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sortField === $campo) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $campo;
            $this->sortDirection = 'asc';
        }
    }

    public function mount($modo = 'full')
    {
        $this->modo = in_array($modo, ['full', 'lectura'], true) ? $modo : 'full';
        $this->authorizeRead();
    }

    public function updatingFiltroInstitucion()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage($value)
    {
        if (! in_array((int) $value, self::PER_PAGE, true)) {
            $this->perPage = 10;
        } else {
            $this->perPage = (int) $value;
        }

        $this->resetPage();
    }

    public function edit($id)
    {
        $this->authorizeWrite();

        $guia = Guias::findOrFail($id);

        // Asigna TODOS los campos incluyendo el ID
        $this->editId = $guia->id;
        $this->editNombre = $guia->nombre_completo;
        $this->editRut = $guia->rut;
        $this->editEmail = $guia->email;
        $this->editTelefono1 = $guia->telefono1;
        $this->editTelefono2 = $guia->telefono2;
        $this->editInstitucion = $guia->institucion;

        // Dispara el evento para abrir el modal
        $this->dispatch('open-edit-modal');
    }

    public function update()
    {
        $this->authorizeWrite();

        $this->validate([
            'editNombre' => 'required|min:3|max:100',
            'editRut' => 'nullable|max:20',
            'editEmail' => 'nullable|email|max:100',
            'editTelefono1' => 'nullable|max:20',
            'editTelefono2' => 'nullable|max:20',
            'editInstitucion' => 'required|numeric|between:1,11',
        ]);

        $guia = Guias::findOrFail($this->editId);
        $guia->update([
            'nombre_completo' => $this->editNombre,
            'rut' => $this->editRut,
            'email' => $this->editEmail,
            'telefono1' => $this->editTelefono1,
            'telefono2' => $this->editTelefono2,
            'institucion' => $this->editInstitucion,
        ]);

        $this->dispatch('close-edit-modal');
        $this->dispatch('alerta-exito');
    }

    public function confirmDelete($id)
    {
        $this->authorizeWrite();

        $this->js("confirmDestructiveAction('¿Eliminar guía?', 'Esta acción no se puede deshacer.').then(result => { if (result.isConfirmed) \$wire.call('performDelete', $id) })");
    }

    public function performDelete($id)
    {
        $this->authorizeWrite();

        Guias::destroy($id);
        $this->dispatch('alerta-deleted');
    }

    public function create()
    {
        $this->authorizeWrite();

        $this->validate([
            'newNombre' => 'required|min:3|max:100',
            'newRut' => 'nullable|max:20',
            'newEmail' => 'nullable|email|max:100',
            'newTelefono1' => 'nullable|max:20',
            'newTelefono2' => 'nullable|max:20',
            'newInstitucion' => 'required|numeric|between:1,11',
        ]);

        Guias::create([
            'nombre_completo' => $this->newNombre,
            'rut' => $this->newRut,
            'email' => $this->newEmail,
            'telefono1' => $this->newTelefono1,
            'telefono2' => $this->newTelefono2,
            'institucion' => $this->newInstitucion,
        ]);

        $this->resetCreateForm();
        $this->dispatch('alerta-exito');
    }

    public function resetCreateForm()
    {
        $this->reset([
            'newNombre', 'newRut', 'newEmail',
            'newTelefono1', 'newTelefono2', 'newInstitucion',
        ]);
        $this->resetErrorBag();
        $this->showCreateForm = false;
    }

    public function render()
    {
        $this->authorizeRead();

        $sortField = in_array($this->sortField, $this->sortableFields(), true) ? $this->sortField : 'nombre_completo';
        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) $this->perPage, self::PER_PAGE, true) ? (int) $this->perPage : 10;
        $filtroInstitucion = in_array((int) $this->filtroInstitucion, range(1, 11), true) ? (int) $this->filtroInstitucion : null;

        $query = Guias::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nombre_completo', 'like', '%'.$this->search.'%')
                        ->when($this->modo !== 'lectura', fn ($q) => $q->orWhere('rut', 'like', '%'.$this->search.'%'))
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('telefono1', 'like', '%'.$this->search.'%')
                        ->orWhere('telefono2', 'like', '%'.$this->search.'%')
                        ->orWhere('institucion', 'like', '%'.$this->search.'%');
                });
            })
            ->when($filtroInstitucion, function ($query) use ($filtroInstitucion) {
                $query->where('institucion', $filtroInstitucion);
            })
            ->orderBy($sortField, $sortDirection);

        return view($this->modo === 'lectura' ? 'livewire.tabla-guias-publica' : 'livewire.tabla-guias', [
            'guias' => $query->paginate($perPage),
            'modo' => $this->modo,
            'instituciones' => [
                1 => '4toTOPSTGO',
                2 => 'Gendarmeria',
                3 => 'Fiscalia',
                4 => 'CAPJ',
                5 => 'Zonal STGO',
                6 => 'Defensoria',
                7 => 'Min. Interior',
                8 => 'C.D. Estado',
                9 => 'Defensores Privados',
                10 => 'PDI/Carabineros',
                11 => 'SML',
            ],
        ]);
    }
}
