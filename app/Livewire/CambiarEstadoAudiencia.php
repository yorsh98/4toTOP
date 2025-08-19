<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Carbon\Carbon;

class CambiarEstadoAudiencia extends Component
{
    public $audiencias = [];
    public $ultimaActualizacion;

    public function mount()
    {
        $this->cargarAudiencias(true); // Forzar carga inicial
    }

    // Añade parámetro para forzar recarga desde DB
    public function cargarAudiencias($forceRefresh = false)
    {
        if ($forceRefresh) {
            // Limpiar cache de relaciones si es necesario
            Audiencia::all()->each->refresh();
        }

        $this->audiencias = Audiencia::whereDate('fecha', Carbon::today())
            ->orderBy('hora_inicio')
            ->get()
            ->fresh(); // Obtener instancias frescas

        $this->ultimaActualizacion = now()->format('H:i:s');
    }

    public function cambiarEstado($id, $nuevoEstado)
    {
        $audiencia = Audiencia::findOrFail($id);
        
        // Actualizar y obtener la audiencia fresca
        $audiencia->update(['estado' => $nuevoEstado]);
        $audiencia->refresh(); // Forzar recarga de la instancia

        // Recargar TODAS las audiencias para mantener consistencia
        $this->cargarAudiencias(true);

        // Opcional: Notificación de éxito
        $this->dispatch('notificacion', [
            'tipo' => 'success',
            'mensaje' => 'Estado actualizado correctamente'
        ]);
    }

    public function render()
    {
        // Forzar recarga antes de renderizar (opcional)
        // $this->cargarAudiencias(true);
        
        return view('livewire.cambiar-estado-audiencia');
    }
}