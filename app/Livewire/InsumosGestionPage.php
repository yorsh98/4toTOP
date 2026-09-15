<?php

namespace App\Livewire;

use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoSolicitud;
use App\Models\Sector;
use App\Services\InsumoGestionService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsumosGestionPage extends Component
{
    use WithPagination;

    public ?int $selectedSolicitudId = null;

    /** @var array<int, array{cantidad: int, sector_id: int|null}> */
    public array $entregas = [];

    public string $motivoRechazo = '';

    public ?int $directRecipientId = null;

    public ?int $directInsumoId = null;

    public int $directCantidad = 1;

    public string $directObservacion = '';

    /** @var array<string, array{insumo_id: int, sector_id: int, cantidad: int}> */
    public array $directCart = [];

    public function mount(): void
    {
        Gate::authorize('insumos.gestionar');
    }

    public function seleccionarSolicitud(int $solicitudId): void
    {
        Gate::authorize('insumos.gestionar');

        $solicitud = $this->solicitudConDetalles($solicitudId);
        $this->selectedSolicitudId = $solicitud->id;
        $this->entregas = $solicitud->detalles->mapWithKeys(function ($detalle): array {
            $entregado = $detalle->movimientos->where('tipo', 'asignacion')->sum('cantidad');
            $existencia = $detalle->insumo->existencias->firstWhere('cantidad', '>', 0);

            return [$detalle->id => [
                'cantidad' => max($detalle->cantidad_solicitada - $entregado, 0),
                'sector_id' => $existencia?->sector_id,
            ]];
        })->all();
        $this->motivoRechazo = '';
    }

    public function entregar(InsumoGestionService $gestionService): void
    {
        Gate::authorize('insumos.gestionar');

        if ($this->selectedSolicitudId === null) {
            return;
        }

        $entregas = collect($this->entregas)
            ->filter(fn (array $entrega): bool => (int) ($entrega['cantidad'] ?? 0) > 0)
            ->map(fn (array $entrega, $detalleId): array => [
                'solicitud_detalle_id' => (int) $detalleId,
                'sector_id' => (int) ($entrega['sector_id'] ?? 0),
                'cantidad' => (int) $entrega['cantidad'],
            ])
            ->values()
            ->all();

        try {
            $gestionService->entregar(InsumoSolicitud::query()->findOrFail($this->selectedSolicitudId), $entregas, auth()->id());
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->dispatch('app-toast', icon: 'error', message: $exception->getMessage());

            return;
        }

        $this->reset('selectedSolicitudId', 'entregas', 'motivoRechazo');
        $this->dispatch('app-toast', icon: 'success', message: 'Entrega registrada correctamente.');
    }

    public function rechazar(InsumoGestionService $gestionService): void
    {
        Gate::authorize('insumos.gestionar');

        if ($this->selectedSolicitudId === null) {
            return;
        }

        try {
            $gestionService->rechazar(InsumoSolicitud::query()->findOrFail($this->selectedSolicitudId), auth()->id(), $this->motivoRechazo);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->dispatch('app-toast', icon: 'error', message: $exception->getMessage());

            return;
        }

        $this->reset('selectedSolicitudId', 'entregas', 'motivoRechazo');
        $this->dispatch('app-toast', icon: 'success', message: 'Solicitud rechazada.');
    }

    public function agregarAsignacionDirecta(): void
    {
        Gate::authorize('insumos.gestionar');

        $this->validate([
            'directInsumoId' => ['required', 'integer', 'exists:insumos,id'],
            'directCantidad' => ['required', 'integer', 'min:1'],
        ]);

        $key = (string) $this->directInsumoId;
        $this->directCart[$key] = [
            'insumo_id' => $this->directInsumoId,
            'cantidad' => ($this->directCart[$key]['cantidad'] ?? 0) + $this->directCantidad,
        ];
    }

    public function quitarAsignacionDirecta(string $key): void
    {
        Gate::authorize('insumos.gestionar');

        unset($this->directCart[$key]);
    }

    public function asignarDirecto(InsumoGestionService $gestionService): void
    {
        Gate::authorize('insumos.gestionar');
        $this->validate(['directRecipientId' => ['required', 'integer', 'exists:funcionarios,id']]);

        try {
            $gestionService->asignarDirecto(array_values($this->directCart), auth()->id(), $this->directRecipientId, filled($this->directObservacion) ? $this->directObservacion : null);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->dispatch('app-toast', icon: 'error', message: $exception->getMessage());

            return;
        }

        $this->reset('directRecipientId', 'directInsumoId', 'directCantidad', 'directObservacion', 'directCart');
        $this->directCantidad = 1;
        $this->dispatch('app-toast', icon: 'success', message: 'Asignacion directa registrada.');
    }

    public function render()
    {
        Gate::authorize('insumos.gestionar');

        return view('livewire.insumos-gestion-page', [
            'solicitudes' => InsumoSolicitud::query()
                ->where('estado', 'pendiente')
                ->with('solicitante')
                ->latest('id')
                ->paginate(10),
            'selectedSolicitud' => $this->selectedSolicitudId ? $this->solicitudConDetalles($this->selectedSolicitudId) : null,
            'insumos' => Insumo::query()->where('activo', true)->orderBy('nombre')->get(),
            'sectores' => Sector::query()->orderBy('nombre')->get(),
            'funcionarios' => Funcionario::query()
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    private function solicitudConDetalles(int $solicitudId): InsumoSolicitud
    {
        return InsumoSolicitud::query()
            ->with(['solicitante', 'detalles.movimientos', 'detalles.insumo.existencias.sector'])
            ->findOrFail($solicitudId);
    }
}
