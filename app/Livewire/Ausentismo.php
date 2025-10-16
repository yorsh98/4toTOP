<?php

namespace App\Livewire;

use App\Models\Ausentismo as AusentismoModel;
use Livewire\Component;
use Livewire\WithPagination;

class Ausentismo extends Component
{
    use WithPagination;

    // Filtros/UI
    public string $q = '';
    public int $perPage = 10;
    protected $paginationTheme = 'bootstrap';

    // Formulario
    public ?int $editingId = null;
    public string $funcionario_nombre = '';
    public string $cargo = '';
    public string $tipo_permiso = '';   // <-- NUEVO nombre correcto
    public string $fecha_inicio = '';
    public string $fecha_termino = '';
    public ?string $observacion = null;

    // Listas para selects
    public array $cargos = [
        'Juez/a', 'Administrador/a', 'Administrativo/a', 'Encargado/a de Causa',
        'Jefe/a de Unidad', 'Informatico',
    ];

    public array $tipos_ausencia = [ // <-- plural para la lista
        'Licencia Medica', 'Permiso 347', 'Permiso sin goce de sueldo', 'Feriado Legal',
        'Curso Academia', 'Comision de servicio', 'Otro',
    ];

    protected function rules(): array
    {
        return [
            'funcionario_nombre' => ['required','string','min:3','max:150'],
            'cargo'              => ['required','string','min:2','max:120'],
            'tipo_permiso'      => ['required','string'], // <-- actualizado
            'fecha_inicio'       => ['required','date'],
            'fecha_termino'      => ['required','date','after_or_equal:fecha_inicio'],
            'observacion'        => ['nullable','string'],
        ];
    }

    protected $validationAttributes = [
        'funcionario_nombre' => 'nombre del funcionario',
        'cargo'              => 'cargo',
        'tipo_permiso'      => 'tipo de permiso', // <-- actualizado
        'fecha_inicio'       => 'fecha de inicio',
        'fecha_termino'      => 'fecha de término',
        'observacion'        => 'observación',
    ];

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->funcionario_nombre = '';
        $this->cargo = '';
        $this->tipo_permiso = ''; // <-- actualizado
        $this->fecha_inicio = '';
        $this->fecha_termino = '';
        $this->observacion = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->validate();

        AusentismoModel::create([
            'funcionario_nombre' => $this->funcionario_nombre,
            'cargo'              => $this->cargo,
            'tipo_permiso'      => $this->tipo_permiso, // <-- actualizado
            'fecha_inicio'       => $this->fecha_inicio,
            'fecha_termino'      => $this->fecha_termino,
            'observacion'        => $this->observacion,
        ]);

        $this->dispatch('toast', message: 'Ausentismo registrado.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $a = AusentismoModel::findOrFail($id);
        $this->editingId = $a->id;
        $this->funcionario_nombre = $a->funcionario_nombre;
        $this->cargo = $a->cargo;
        $this->tipo_permiso = $a->tipo_permiso; // <-- actualizado
        // Si tienes casts a date en el modelo, esto funciona. Si no, asegurarse de formatear strings.
        $this->fecha_inicio  = $a->fecha_inicio?->format('Y-m-d')  ?? (is_string($a->fecha_inicio)  ? substr($a->fecha_inicio, 0, 10)  : '');
        $this->fecha_termino = $a->fecha_termino?->format('Y-m-d') ?? (is_string($a->fecha_termino) ? substr($a->fecha_termino, 0, 10) : '');
        $this->observacion = $a->observacion;

        $this->dispatch('scrollToForm');
    }

    public function update(): void
    {
        if (!$this->editingId) return;

        $this->validate();

        $a = AusentismoModel::findOrFail($this->editingId);
        $a->update([
            'funcionario_nombre' => $this->funcionario_nombre,
            'cargo'              => $this->cargo,
            'tipo_permiso'      => $this->tipo_permiso, // <-- actualizado
            'fecha_inicio'       => $this->fecha_inicio,
            'fecha_termino'      => $this->fecha_termino,
            'observacion'        => $this->observacion,
        ]);

        $this->dispatch('toast', message: 'Ausentismo actualizado.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        AusentismoModel::whereKey($id)->delete();
        $this->dispatch('toast', message: 'Registro eliminado.');
        $this->resetPage();
    }

    public function render()
    {
        $items = AusentismoModel::query()
            ->when($this->q, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('funcionario_nombre', 'like', "%{$this->q}%")
                       ->orWhere('cargo', 'like', "%{$this->q}%")
                       ->orWhere('tipo_permiso', 'like', "%{$this->q}%") // <-- también busca por tipo
                       ->orWhere('observacion', 'like', "%{$this->q}%");
                });
            })
            ->orderByDesc('fecha_inicio')
            ->paginate($this->perPage);

        // Dejo explícito el pase de variables por si quieres usarlas en el Blade
        return view('livewire.ausentismo', [
            'items'           => $items,
            'cargos'          => $this->cargos,
            'tipos_ausencia'  => $this->tipos_ausencia,
        ]);
    }
}
