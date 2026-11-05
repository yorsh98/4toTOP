<?php

namespace App\Livewire;

use App\Models\Insumo;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Services\InsumoStockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsumosInventarioPage extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $unidad_medida = 'UN';

    public int $stock_minimo = 15;

    public string $search = '';

    public string $estadoFiltro = '';

    #[Locked]
    public ?int $reposicionInsumoId = null;

    public ?int $reposicionSectorId = null;

    public int $reposicionCantidad = 1;

    public string $reposicionObservacion = '';

    public function mount(): void
    {
        Gate::authorize('insumos.inventario');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoFiltro(): void
    {
        $this->resetPage();
    }

    public function guardar(): void
    {
        Gate::authorize('insumos.inventario');

        $validated = $this->validate([
            'codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('insumos', 'codigo')->ignore($this->editingId),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'unidad_medida' => ['required', 'string', 'max:20', Rule::in(array_filter(['UN', $this->editingId ? Insumo::query()->findOrFail($this->editingId)->unidad_medida : null]))],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        if ($this->editingId === null) {
            Insumo::query()->create($validated);
            $message = 'Insumo creado correctamente.';
        } else {
            Insumo::query()->findOrFail($this->editingId)->update($validated);
            $message = 'Insumo actualizado correctamente.';
        }

        $this->resetFormulario();
        $this->dispatch('app-toast', icon: 'success', message: $message);
    }

    public function editar(int $insumoId): void
    {
        Gate::authorize('insumos.inventario');

        $insumo = Insumo::query()->findOrFail($insumoId);
        $this->editingId = $insumo->id;
        $this->codigo = $insumo->codigo;
        $this->nombre = $insumo->nombre;
        $this->unidad_medida = $insumo->unidad_medida;
        $this->stock_minimo = $insumo->stock_minimo;
        $this->resetValidation();
    }

    public function alternarActivo(int $insumoId): void
    {
        Gate::authorize('insumos.inventario');

        $insumo = Insumo::query()->findOrFail($insumoId);
        $insumo->update(['activo' => ! $insumo->activo]);

        $this->dispatch('app-toast', icon: 'success', message: $insumo->activo ? 'Insumo activado.' : 'Insumo desactivado.');
    }

    public function abrirReposicion(int $insumoId): void
    {
        Gate::authorize('insumos.inventario');

        $this->reposicionInsumoId = Insumo::query()->findOrFail($insumoId)->id;
        $this->reposicionSectorId = null;
        $this->reposicionCantidad = 1;
        $this->reposicionObservacion = '';
        $this->resetValidation();
    }

    public function registrarReposicion(InsumoStockService $stockService): void
    {
        Gate::authorize('insumos.inventario');

        $validated = $this->validate([
            'reposicionInsumoId' => ['required', 'integer', 'exists:insumos,id'],
            'reposicionSectorId' => ['required', 'integer', 'exists:sectors,id'],
            'reposicionCantidad' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'reposicionObservacion' => ['nullable', 'string', 'max:5000'],
        ]);

        $stockService->ingresar(
            $validated['reposicionInsumoId'],
            $validated['reposicionSectorId'],
            $validated['reposicionCantidad'],
            auth()->id(),
            filled($validated['reposicionObservacion']) ? $validated['reposicionObservacion'] : null,
        );

        $this->cerrarReposicion();
        $this->dispatch('app-toast', icon: 'success', message: 'Reposicion registrada correctamente.');
    }

    public function cerrarReposicion(): void
    {
        $this->reset('reposicionInsumoId', 'reposicionSectorId', 'reposicionObservacion');
        $this->reposicionCantidad = 1;
        $this->resetValidation();
    }

    public function resetFormulario(): void
    {
        $this->reset('editingId', 'codigo', 'nombre', 'unidad_medida');
        $this->stock_minimo = 15;
        $this->unidad_medida = 'UN';
        $this->resetValidation();
    }

    public function render()
    {
        Gate::authorize('insumos.inventario');

        $search = addcslashes(mb_substr(trim($this->search), 0, 255), '%_\\');
        $estadoFiltro = in_array($this->estadoFiltro, ['', 'activos', 'inactivos', 'bajo'], true) ? $this->estadoFiltro : '';
        $insumos = Insumo::query()
            ->withSum('existencias', 'cantidad')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('codigo', 'like', "%{$search}%")
                ->orWhere('nombre', 'like', "%{$search}%")))
            ->when($estadoFiltro === 'activos', fn ($query) => $query->where('activo', true))
            ->when($estadoFiltro === 'inactivos', fn ($query) => $query->where('activo', false))
            ->when($estadoFiltro === 'bajo', fn ($query) => $this->whereStockBajo($query))
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.insumos-inventario-page', [
            'insumos' => $insumos,
            'sectores' => Sector::query()->orderBy('nombre')->get(),
            'movimientos' => InsumoMovimiento::query()
                ->with(['insumo', 'sector', 'createdBy', 'asignadoA', 'asignadoFuncionario'])
                ->latest('id')
                ->paginate(15, pageName: 'movimientosPage'),
            'totalUnidades' => (int) Insumo::query()->withSum('existencias', 'cantidad')->get()->sum('existencias_sum_cantidad'),
            'stockBajo' => Insumo::query()
                ->tap(fn ($query) => $this->whereStockBajo($query))
                ->count(),
        ]);
    }

    private function whereStockBajo($query): void
    {
        $query->whereRaw('(SELECT COALESCE(SUM(cantidad), 0) FROM insumo_existencias WHERE insumo_existencias.insumo_id = insumos.id) <= insumos.stock_minimo');
    }
}
