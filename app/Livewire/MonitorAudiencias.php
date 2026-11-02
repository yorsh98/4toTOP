<?php

namespace App\Livewire;

use App\Models\Audiencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MonitorAudiencias extends Component
{
    public $currentTypeIndex = 0;

    public $audienciaTypes = ['Juicio Oral', 'Cont. Juicio Oral', 'Audiencia Corta', 'Lectura de Sentencia'];

    public $rotationInterval = 7; // segundos

    public $currentView = 0;

    public $rowsPerView = 6; // el navegador lo ajusta a las filas que caben en su pantalla

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
        $this->currentView++;

        if ($this->currentView < $this->viewsOf($this->todaysAudiencias()->count())) {
            return;
        }

        $this->currentView = 0;
        $this->currentTypeIndex = ($this->currentTypeIndex + 1) % count($this->audienciaTypes);
    }

    private function todaysAudiencias()
    {
        return Audiencia::whereDate('fecha', Carbon::today())
            ->where('tipo_audiencia', $this->getCurrentType())
            ->orderBy('hora_inicio');
    }

    private function viewsOf(int $total): int
    {
        return max(1, (int) ceil($total / max(1, (int) $this->rowsPerView)));
    }

    public function getCurrentType()
    {
        return $this->audienciaTypes[$this->currentTypeIndex];
    }

    public function render()
    {
        $audiencias = $this->todaysAudiencias()->get();
        $views = $this->viewsOf($audiencias->count());
        $this->currentView = min(max(0, (int) $this->currentView), $views - 1);

        return view('livewire.monitor-audiencias', [
            'audiencias' => $audiencias->forPage($this->currentView + 1, max(1, (int) $this->rowsPerView)),
            'currentType' => $this->getCurrentType(),
            'views' => $views,
        ]);
    }

    // método para CAMBIO DE ESTADO en AUDIENCIAS CORTAS (solo las de HOY)
    public function actualizarEstadoAudienciasCortas($estado)
    {
        Gate::authorize('audiencias.estado.cambiar');

        // Usa la zona horaria de la app (config/app.php => 'timezone' => 'America/Santiago')
        $hoy = Carbon::today(config('app.timezone'));

        Audiencia::where('tipo_audiencia', 'Audiencia Corta')
            ->whereDate('fecha', $hoy) // 👈 filtra solo las de hoy
            ->when($estado === 'EN_CURSO', fn ($q) => $q->whereIn('estado', ['POR_REALIZARSE'])) // opcional, mantiene flujo lógico
            ->when($estado === 'FINALIZADA', fn ($q) => $q->whereIn('estado', ['EN_CURSO', 'RECESO'])) // opcional, evita tocar ya finalizadas
            ->update([
                'estado' => $estado,
                'updated_at' => now(),
            ]);
    }
}
