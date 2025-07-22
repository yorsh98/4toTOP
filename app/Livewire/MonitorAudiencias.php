<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;

class MonitorAudiencias extends Component
{
    public $fecha;

    public function mount()
    {
        $this->fecha = now()->format('d-m-Y');
    }

    public function render()
    {
        $audiencias = Audiencia::whereDate('fecha', $this->fecha)
            ->orderBy('hora_inicio')
            ->get();

        return view('livewire.monitor-audiencias', [
            'audiencias' => $audiencias
        ]);
    }
}