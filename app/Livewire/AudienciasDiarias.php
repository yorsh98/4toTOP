<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AudienciasDiarias extends Component
{
    public string $fecha;
    public $audiencias = [];

    public function mount(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        $this->fecha = now()->toDateString();
        $this->buscar();
    }

    public function updatedFecha(): void
    {
        // al cambiar la fecha con el date picker
        $this->buscar();
    }

    public function hoy(): void
    {
        $this->fecha = now()->toDateString();
        $this->buscar();
    }

    public function diaAnterior(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->toDateString();
        $this->buscar();
    }

    public function diaSiguiente(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->toDateString();
        $this->buscar();
    }

    public function buscar(): void
    {
        $this->audiencias = Audiencia::query()
            ->whereDate('fecha', $this->fecha)
            ->orderBy('sala')
            ->orderBy('hora_inicio')
            ->limit(200) // por si algún día se dispara
            ->get()
            ->map(function ($a) {
                $a->acusados = is_array($a->acusados) ? $a->acusados : (empty($a->acusados) ? [] : (array) $a->acusados);
                $a->jueces_inhabilitados = is_array($a->jueces_inhabilitados) ? $a->jueces_inhabilitados : [];
                return $a;
            });

        $this->dispatch('busqueda-lista');
    }

    public function eliminar(int $id): void
    {
        abort_unless(Auth::check(), 403);
        $aud = Audiencia::find($id);
        if (!$aud) return;

        $aud->delete();
        $this->buscar();
        $this->dispatch('audiencia-eliminada');
    }

    public function render()
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        return view('livewire.audiencias-diarias');
    }
}
