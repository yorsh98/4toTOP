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

    // Formulario
    public ?int $editingId = null;
    public string $funcionario_nombre = '';
    public string $cargo = '';
    public string $fecha_inicio = '';
    public string $fecha_termino = '';
    public ?string $observacion = null;

    public array $cargos = [
        'Juez/a', 'Administrador/a', 'Administrativo/a', 'Encargado/a de Causa',
        'Jefe/a de Unidad', 'Informatico', 
    ];

    protected function rules(): array
    {
        return [
            'funcionario_nombre' => ['required','string','min:3','max:150'],
            'cargo'              => ['required','string','min:2','max:120'],
            'fecha_inicio'       => ['required','date'],
            'fecha_termino'      => ['required','date','after_or_equal:fecha_inicio'],
            'observacion'        => ['nullable','string'],
        ];
    }

    protected $validationAttributes = [
        'funcionario_nombre' => 'nombre del funcionario',
        'cargo'              => 'cargo',
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
        $this->fecha_inicio = $a->fecha_inicio?->format('Y-m-d') ?? '';
        $this->fecha_termino = $a->fecha_termino?->format('Y-m-d') ?? '';
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
            ->when($this->q, fn($q) =>
                $q->where(function($qq){
                    $qq->where('funcionario_nombre', 'like', "%{$this->q}%")
                      ->orWhere('cargo', 'like', "%{$this->q}%")
                      ->orWhere('observacion', 'like', "%{$this->q}%");
                })
            )
            ->orderByDesc('fecha_inicio')
            ->paginate($this->perPage);

        return view('livewire.ausentismo', [
            'items' => $items,
        ]);
    }
}
