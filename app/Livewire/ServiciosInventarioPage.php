<?php

namespace App\Livewire;

use App\Models\Activo;
use App\Models\Sector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ServiciosInventarioPage extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $search = '';

    public string $estadoFiltro = '';

    public string $sectorFiltro = '';

    public string $nombre = '';

    public ?int $sector_id = null;

    public string $codigo_inventario = '';

    public string $numero_serie = '';

    public string $estado = 'Operativo';

    public string $observaciones = '';

    /** @return array<int, string> */
    public function estados(): array
    {
        return ['Operativo', 'En Reparación', 'Baja'];
    }

    public function mount(): void
    {
        Gate::authorize('servicios.ver');
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sector_id' => ['required', 'integer', Rule::exists('sectors', 'id')],
            'codigo_inventario' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('activos', 'codigo_inventario')->ignore($this->editingId),
            ],
            'numero_serie' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in($this->estados())],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedSectorFiltro(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? 'servicios.editar' : 'servicios.crear');

        $validated = $this->validate();
        $validated['codigo_inventario'] = $validated['codigo_inventario'] ?: null;
        $validated['numero_serie'] = $validated['numero_serie'] ?: null;
        $validated['observaciones'] = $validated['observaciones'] ?: null;

        if ($this->editingId) {
            Activo::query()->findOrFail($this->editingId)->update($validated);
            session()->flash('ok', 'Activo actualizado correctamente.');
        } else {
            Activo::query()->create($validated);
            session()->flash('ok', 'Activo creado correctamente.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        Gate::authorize('servicios.editar');

        $activo = Activo::query()->findOrFail($id);

        $this->editingId = $activo->id;
        $this->nombre = $activo->nombre;
        $this->sector_id = $activo->sector_id;
        $this->codigo_inventario = (string) $activo->codigo_inventario;
        $this->numero_serie = (string) $activo->numero_serie;
        $this->estado = $activo->estado;
        $this->observaciones = (string) $activo->observaciones;
    }

    public function delete(int $id): void
    {
        Gate::authorize('servicios.eliminar');

        Activo::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('ok', 'Activo eliminado correctamente.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'nombre',
            'sector_id',
            'codigo_inventario',
            'numero_serie',
            'observaciones',
        ]);

        $this->estado = 'Operativo';
        $this->resetValidation();
    }

    public function render()
    {
        $search = trim($this->search);

        $activos = Activo::query()
            ->with('sector')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo_inventario', 'like', "%{$search}%")
                        ->orWhere('numero_serie', 'like', "%{$search}%")
                        ->orWhereHas('sector', function ($sectorQuery) use ($search) {
                            $sectorQuery->where('nombre', 'like', "%{$search}%")
                                ->orWhere('identificador_mapa', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->estadoFiltro !== '', function ($query) {
                $query->where('estado', $this->estadoFiltro);
            })
            ->when($this->sectorFiltro !== '', function ($query) {
                $query->where('sector_id', $this->sectorFiltro);
            })
            ->latest('id')
            ->paginate(12);

        return view('livewire.servicios-inventario-page', [
            'activos' => $activos,
            'sectores' => Sector::query()->orderBy('nombre')->get(),
            'estados' => $this->estados(),
            'totalActivos' => Activo::query()->count(),
        ]);
    }
}
