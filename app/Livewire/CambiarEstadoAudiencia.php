<?php

namespace App\Livewire;

use App\Models\Audiencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CambiarEstadoAudiencia extends Component
{
    #[Locked]
    public $audiencias = [];

    #[Locked]
    public $ultimaActualizacion;

    public $filtro = null; // <-- filtro por tipo de audiencia

    #[Locked]
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
        $this->filtro = in_array($tipo, $this->tiposAudiencia, true) ? $tipo : null;
        $this->cargarAudiencias(true);
    }

    public function cargarAudiencias($forceRefresh = false)
    {
        // Si no hay filtro seleccionado, no mostrar audiencias
        if (! in_array($this->filtro, $this->tiposAudiencia, true)) {
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

        if (! in_array($nuevoEstado, ['POR_REALIZARSE', 'EN_CURSO', 'RECESO', 'FINALIZADA'], true)) {
            return;
        }

        $audiencia = Audiencia::query()->whereDate('fecha', Carbon::today())->findOrFail($id);
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
