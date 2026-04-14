<?php

namespace App\Livewire;

use App\Models\Turno;
use App\Services\FuncionarioCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TurnoPage extends Component
{
    public array $turno1 = [];

    public array $turno2 = [];

    public array $turno3 = [];

    public function mount(): void
    {
        Gate::authorize('turnos.ver');

        $this->ensureBaseRecords();
        $this->hydrateState();
    }

    public function saveTurno1(): void
    {
        Gate::authorize('turnos.editar');

        $data = $this->validateTurno1();

        Turno::query()->findOrFail(1)->update($data);

        session()->flash('success', 'Turno 1 actualizado correctamente.');
        $this->hydrateState();
    }

    public function saveTurno2Acd(): void
    {
        Gate::authorize('turnos.editar');

        $data = $this->validateTurno2();

        Turno::query()->findOrFail(2)->update($data);

        session()->flash('success', 'Turno 2 ACD actualizado correctamente.');
        $this->hydrateState();
    }

    public function saveTurno3(): void
    {
        Gate::authorize('turnos.editar');

        $data = $this->validateTurno3();

        Turno::query()->findOrFail(2)->update($data);

        session()->flash('success', 'Turno 3 actualizado correctamente.');
        $this->hydrateState();
    }

    public function render()
    {
        Gate::authorize('turnos.ver');

        $catalog = app(FuncionarioCatalog::class);

        return view('livewire.turno-page', [
            'magistrados' => $catalog->nombresByContext('turnos.magistrados'),
            'funcionarios' => $catalog->nombresByContext('turnos.funcionarios'),
            'jefaturas' => $catalog->nombresByContext('turnos.jefatura'),
        ]);
    }

    private function ensureBaseRecords(): void
    {
        if (Turno::query()->count() === 0) {
            Turno::query()->create([]);
            Turno::query()->create([]);
        }
    }

    private function hydrateState(): void
    {
        $turno1 = Turno::query()->find(1);
        $turno2 = Turno::query()->find(2);

        $this->turno1 = [
            'TM1' => $turno1?->TM1 ?? '',
            'TM2' => $turno1?->TM2 ?? '',
            'TM3' => $turno1?->TM3 ?? '',
            'TF1' => $turno1?->TF1 ?? '',
            'TF2' => $turno1?->TF2 ?? '',
            'TF3' => $turno1?->TF3 ?? '',
            'TJ1' => $turno1?->TJ1 ?? '',
            'TJ2' => $turno1?->TJ2 ?? '',
            'TJ3' => $turno1?->TJ3 ?? '',
        ];

        $this->turno2 = [
            'ACDM1' => $turno2?->ACDM1 ?? '',
            'ACDM2' => $turno2?->ACDM2 ?? '',
            'ACDM3' => $turno2?->ACDM3 ?? '',
            'ACDF1' => $turno2?->ACDF1 ?? '',
            'ACDF2' => $turno2?->ACDF2 ?? '',
            'ACDF3' => $turno2?->ACDF3 ?? '',
            'ACDJ1' => $turno2?->ACDJ1 ?? '',
            'ACDJ2' => $turno2?->ACDJ2 ?? '',
            'ACDJ3' => $turno2?->ACDJ3 ?? '',
            'FECHA' => $turno2?->FECHA ?? '',
        ];

        $this->turno3 = [
            'TM1' => $turno2?->TM1 ?? '',
            'TM2' => $turno2?->TM2 ?? '',
            'TM3' => $turno2?->TM3 ?? '',
        ];
    }

    private function validateTurno1(): array
    {
        return $this->validate([
            'turno1.TM1' => ['nullable', 'string', 'max:255'],
            'turno1.TM2' => ['nullable', 'string', 'max:255'],
            'turno1.TM3' => ['nullable', 'string', 'max:255'],
            'turno1.TF1' => ['nullable', 'string', 'max:255'],
            'turno1.TF2' => ['nullable', 'string', 'max:255'],
            'turno1.TF3' => ['nullable', 'string', 'max:255'],
            'turno1.TJ1' => ['nullable', 'string', 'max:255'],
            'turno1.TJ2' => ['nullable', 'string', 'max:255'],
            'turno1.TJ3' => ['nullable', 'string', 'max:255'],
        ])['turno1'];
    }

    private function validateTurno2(): array
    {
        return $this->validate([
            'turno2.ACDM1' => ['nullable', 'string', 'max:255'],
            'turno2.ACDM2' => ['nullable', 'string', 'max:255'],
            'turno2.ACDM3' => ['nullable', 'string', 'max:255'],
            'turno2.ACDF1' => ['nullable', 'string', 'max:255'],
            'turno2.ACDF2' => ['nullable', 'string', 'max:255'],
            'turno2.ACDF3' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ1' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ2' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ3' => ['nullable', 'string', 'max:255'],
            'turno2.FECHA' => ['nullable', 'string'],
        ])['turno2'];
    }

    private function validateTurno3(): array
    {
        return $this->validate([
            'turno3.TM1' => ['nullable', 'string', 'max:255'],
            'turno3.TM2' => ['nullable', 'string', 'max:255'],
            'turno3.TM3' => ['nullable', 'string', 'max:255'],
        ])['turno3'];
    }
}
