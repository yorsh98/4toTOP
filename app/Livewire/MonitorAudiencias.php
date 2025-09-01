<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Carbon\Carbon;

class MonitorAudiencias extends Component
{
    public $currentTypeIndex = 0;
    public $audienciaTypes = ['Juicio Oral', 'Cont. Juicio Oral', 'Audiencia Corta', 'Lectura de Sentencia'];
    public $rotationInterval = 7; // segundos

    protected $listeners = ['startRotation', 'stopRotation', 'keepAlive'];

    public function keepAlive()
    {
        // Mantiene activa la sesión
    }
    public function mount()
    {
        $this->startRotation();
    }

    public function startRotation()
    {
        // Iniciar o reiniciar el intervalo
        $this->dispatch('startRotationJS', duration: $this->rotationInterval);
    }

    public function stopRotation()
    {
        $this->dispatch('stopRotationJS');
    }

    public function rotate()
    {
        $this->currentTypeIndex = ($this->currentTypeIndex + 1) % count($this->audienciaTypes);
    }

    public function getCurrentType()
    {
        return $this->audienciaTypes[$this->currentTypeIndex];
    }

    public function render()
    {
        $currentType = $this->getCurrentType();
        $audiencias = Audiencia::whereDate('fecha', Carbon::today())
            ->where('tipo_audiencia', $currentType)
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('tipo_audiencia');

        return view('livewire.monitor-audiencias', [
            'audiencias' => $audiencias,
            'currentType' => $currentType
        ]);
    }

    //metodo para CAMBIO DE ESTADO en AUDIENCIAS CORTAS
    public function actualizarEstadoAudienciasCortas($estado)
    {
        // Solo actualizamos las audiencias de tipo "Audiencia Corta" que estén en un estado lógico previo
        \App\Models\Audiencia::where('tipo_audiencia', 'Audiencia Corta')
            ->whereIn('estado', ['POR_REALIZARSE', 'EN_CURSO']) // Evita cambiar ya finalizadas
            ->update([
                'estado' => $estado,
                'updated_at' => now(),
            ]);
    }
    
}