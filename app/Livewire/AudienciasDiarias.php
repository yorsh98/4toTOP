<?php

// app/Livewire/AudienciasDiarias.php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\PrograMailController;

class AudienciasDiarias extends Component
{
    public string $fecha;
    public $audiencias = [];

    // NUEVO: UI del slide-over + correo
    public bool $showSend = false;
    public string $toEmail = '';

    public function mount(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        $this->fecha = now()->toDateString();
        $this->buscar();
    }

    public function updatedFecha(): void { $this->buscar(); }

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
            ->limit(200)
            ->get()
            ->values();

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

    // NUEVO: envío individual usando el controlador directamente (sin hardcodear URL)
    public function enviarCorreoIndividual(): void
    {
        $this->validate([
            'toEmail' => ['required','email','max:190'],
            'fecha'   => ['required'],
        ], [
            'toEmail.required' => 'Ingresa un correo.',
            'toEmail.email'    => 'Formato de correo no válido.',
        ]);

        // Llama al controlador y pasa el correo como $singleTo
        app(PrograMailController::class)->enviarProgramacionPorCorreo($this->fecha, $this->toEmail);

        $this->reset('toEmail');
        $this->showSend = false;

        $this->dispatch('correo-enviado');
    }

    public function render()
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        return view('livewire.audiencias-diarias');
    }
}
