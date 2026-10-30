<?php

namespace App\Livewire;

use App\Models\Audiencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class AudienciasDiarias extends Component
{
    public string $fecha;

    public bool $showSend = false;

    public ?int $editId = null;

    public ?string $editTab = null;

    public function mount(): void
    {
        Gate::authorize('audiencias.ver');
        $this->fecha = now()->toDateString();
    }

    public function diaAnterior(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->toDateString();
    }

    public function hoy(): void
    {
        $this->fecha = now()->toDateString();
    }

    public function diaSiguiente(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->toDateString();
    }

    public function openSend(): void
    {
        Gate::authorize('progra.mail.enviar');

        if ($this->showSend) {
            $this->dispatch('audiencias-correo-abrir');

            return;
        }

        $this->showSend = true;
    }

    #[On('editar-audiencia')]
    public function openEdit(int $id): void
    {
        Gate::authorize('audiencias.editar');

        $audiencia = Audiencia::findOrFail($id);
        $this->editId = $audiencia->id;
        $this->editTab = match ($audiencia->tipo_audiencia) {
            'Lectura de Sentencia' => 'lecturas',
            'Audiencia Corta' => 'cortas',
            default => 'form',
        };
        $this->dispatch('audiencia-edicion-abierta');
    }

    public function closeEdit(): void
    {
        $this->editId = null;
        $this->editTab = null;
        $this->dispatch('audiencia-edicion-cerrada');
    }

    #[On('audiencias-correo-cerrado')]
    public function closeSend(): void
    {
        $this->showSend = false;
    }

    #[On('audiencia-guardada')]
    public function closeEditAfterSave(?string $fecha = null): void
    {
        // La vista sigue a la fecha guardada: si no, lo recién agregado parece no existir.
        if ($fecha !== null) {
            $this->fecha = $fecha;
        }

        if ($this->editId !== null) {
            $this->closeEdit();
        }
    }

    public function render()
    {
        Gate::authorize('audiencias.ver');

        return view('livewire.audiencias-diarias');
    }
}
