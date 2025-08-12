<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Carbon\Carbon;

class MonitorAudiencias extends Component
{
    public $fecha;

    public function mount()
    {
        $this->fecha = now()->toDateString(); // Formato YYYY-MM-DD
    }

    public function render()
{
    $audiencias = Audiencia::whereDate('fecha', $this->fecha)
        ->orderBy('hora_inicio')
        ->get()
        ->groupBy('tipo_audiencia');
    
    
    
    return view('livewire.monitor-audiencias', [
        'audiencias' => $audiencias,
        'fecha_actual' => Carbon::parse($this->fecha)->isoFormat('dddd D [de] MMMM [de] YYYY')
    ]);
}
}