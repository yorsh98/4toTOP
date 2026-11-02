<?php

namespace App\Livewire;

use App\Models\Audiencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CambiarEstadoAudiencia extends Component
{
    public $audiencias = [];

    public $ultimaActualizacion;

    public $filtro = null; // <-- filtro por tipo de audiencia

    public $tiposAudiencia = [
        'Juicio Oral',
        'Cont. Juicio Oral',
        'Audiencia Corta',
        'Lectura de Sentencia',
    ];

    public function mount()
    {
        $this->cargarAudiencias(true);
    }

    public function setFiltro($tipo)
    {
        $this->filtro = $tipo;
        $this->cargarAudiencias(true);
    }

    public function cargarAudiencias($forceRefresh = false)
    {
        // Si no hay filtro seleccionado, no mostrar audiencias
        if (! $this->filtro) {
            $this->audiencias = collect(); // colección vacía
            $this->ultimaActualizacion = now()->format('H:i:s');

            return;
        }

        $query = Audiencia::whereDate('fecha', Carbon::today())
            ->where('tipo_audiencia', $this->filtro);

        $this->audiencias = $query
            ->orderBy('hora_inicio')
            ->get();

        $this->ultimaActualizacion = now()->format('H:i:s');
    }

    public function cambiarEstado($id, $nuevoEstado)
    {
        Gate::authorize('audiencias.estado.cambiar');

        $audiencia = Audiencia::findOrFail($id);
        $audiencia->update(['estado' => $nuevoEstado]);
        $audiencia->refresh();

        $this->cargarAudiencias(true);

        $this->dispatch('notificacion', [
            'tipo' => 'success',
            'mensaje' => 'Estado actualizado correctamente',
        ]);
    }

    public function render()
    {
        return view('livewire.cambiar-estado-audiencia');
    }
}
