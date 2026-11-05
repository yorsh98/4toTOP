<?php

namespace App\Livewire;

use App\Models\Audiencia;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class AudienciasDiariasTabla extends Component
{
    #[Reactive]
    public string $fecha;

    #[Locked]
    public $audiencias = [];

    #[Locked]
    public ?int $detalleId = null;

    public function mount(): void
    {
        Gate::authorize('audiencias.ver');
        $this->buscar();
    }

    public function updatedFecha(): void
    {
        $this->detalleId = null;
        $this->buscar();
    }

    #[On('audiencia-guardada')]
    public function refreshAudiencias(): void
    {
        $this->buscar();
    }

    public function buscar(): void
    {
        Gate::authorize('audiencias.ver');

        if (validator(['fecha' => $this->fecha], ['fecha' => 'required|date_format:Y-m-d'])->fails()) {
            $this->audiencias = collect();

            return;
        }

        $this->audiencias = Audiencia::query()
            ->whereDate('fecha', $this->fecha)
            ->orderBy('sala')
            ->orderBy('hora_inicio')
            ->limit(200)->get()->values();
    }

    public function toggleDetalle(int $id): void
    {
        $this->detalleId = $this->detalleId === $id ? null : $id;
    }

    public function editar(int $id): void
    {
        Gate::authorize('audiencias.editar');
        $this->dispatch('editar-audiencia', id: $id);
    }

    public function eliminar(int $id): void
    {
        Gate::authorize('audiencias.editar');

        $audiencia = Audiencia::find($id);
        if (! $audiencia) {
            return;
        }

        $audiencia->delete();
        $this->detalleId = null;
        $this->buscar();
        $this->dispatch('audiencia-eliminada');
    }

    public function render()
    {
        Gate::authorize('audiencias.ver');

        return view('livewire.audiencias-diarias-tabla');
    }
}
