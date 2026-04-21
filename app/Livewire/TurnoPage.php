<?php

namespace App\Livewire;

use App\Models\Turno;
use App\NotificationsV2\Application\Services\TurnoOneWeeklySummaryNotifier;
use App\NotificationsV2\Application\Services\TurnoTwoAcdWeeklySummaryNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
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

    public string $semanaTurno1 = '';

    public string $periodoTurno2Acd = '';

    public function mount(): void
    {
        Gate::authorize('turnos.ver');

        $this->ensureBaseRecords();
        $this->hydrateState();
    }

    public function saveTurno1(): void
    {
        Gate::authorize('turnos.editar');

        $validated = $this->validateTurno1();
        $data = $validated['turno1'];
        $semanaTurno = $this->normalizeSemanaTurno((string) ($validated['semanaTurno1'] ?? ''));

        $turno1 = Turno::query()->findOrFail(1);

        $turno1->update(array_merge($data, [
            'FECHA' => $semanaTurno,
        ]));

        session()->flash('success', 'Turno 1 actualizado correctamente.');
        $this->hydrateState();
    }

    public function sendTurno1Summary(): void
    {
        Gate::authorize('turnos.editar');

        $result = $this->turnoOneSummaryNotifier()->notifyForWeekWithResult(forceNewDispatch: true);

        $skipped = $result['skipped'] ?? [];
        $dispatch = $result['dispatch'] ?? null;

        if ($dispatch && $dispatch->status === 'failed') {
            session()->flash('error', 'No se pudo encolar el resumen semanal: '.($dispatch->failure_reason ?? 'sin destinatarios validos.'));

            return;
        }

        if ($skipped !== []) {
            $nombres = collect($skipped)
                ->map(fn (array $item): string => (string) ($item['name'] ?? 'Sin nombre'))
                ->filter()
                ->implode(', ');

            session()->flash('warning', 'Se omitieron destinatarios sin correo valido: '.$nombres.'.');
        }

        session()->flash('success', 'Resumen semanal de Turno 1 encolado correctamente.');
    }

    public function sendTurno2AcdSummary(): void
    {
        Gate::authorize('turnos.editar');

        $result = $this->turnoTwoAcdSummaryNotifier()->notifyForWeekWithResult(forceNewDispatch: true);

        $dispatch = $result['dispatch'] ?? null;

        if ($dispatch && $dispatch->status === 'failed') {
            session()->flash('error', 'No se pudo encolar el resumen Turno 2 ACD: '.($dispatch->failure_reason ?? 'sin destinatarios validos.'));

            return;
        }

        $skipped = $result['skipped'] ?? [];

        if ($skipped !== []) {
            $nombres = collect($skipped)
                ->map(fn (array $item): string => (string) ($item['name'] ?? 'Sin nombre'))
                ->filter()
                ->implode(', ');

            session()->flash('warning', 'Turno 2 ACD: se omitieron destinatarios sin correo valido: '.$nombres.'.');
        }

        session()->flash('success', 'Resumen de Turno 2 ACD encolado correctamente.');
    }

    public function saveTurno2Acd(): void
    {
        Gate::authorize('turnos.editar');

        $validated = $this->validateTurno2();
        $data = $validated['turno2'];
        $periodoTurno = $this->normalizeSemanaTurno((string) ($validated['periodoTurno2Acd'] ?? ''));

        $turno2 = Turno::query()->findOrFail(2);
        $turno2->update(array_merge($data, [
            'FECHA' => $periodoTurno,
        ]));

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
            'lastTurnoSummaryIssue' => $this->latestDispatchIssue('turno1.resumen_semanal.%'),
            'lastTurno2AcdSummaryIssue' => $this->latestDispatchIssue('turno2.acd.resumen_semanal.%'),
        ]);
    }

    private function latestDispatchIssue(string $eventKeyPattern): ?array
    {
        $dispatch = NotificationDispatch::query()
            ->where('event_key', 'like', $eventKeyPattern)
            ->whereNotNull('failure_reason')
            ->latest('id')
            ->first(['id', 'failure_reason']);

        if ($dispatch === null) {
            return null;
        }

        return [
            'id' => (int) $dispatch->id,
            'reason' => (string) $dispatch->failure_reason,
        ];
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
            'TF1_CARGO' => $turno1?->TF1_CARGO ?? '',
            'TF2_CARGO' => $turno1?->TF2_CARGO ?? '',
            'TF3_CARGO' => $turno1?->TF3_CARGO ?? '',
        ];

        $this->semanaTurno1 = (string) ($turno1?->FECHA ?? '');
        $this->periodoTurno2Acd = (string) ($turno2?->FECHA ?? '');

        $this->turno2 = [
            'ACDM1' => $turno2?->ACDM1 ?? '',
            'ACDM2' => $turno2?->ACDM2 ?? '',
            'ACDM3' => $turno2?->ACDM3 ?? '',
            'ACDF1' => $turno2?->ACDF1 ?? '',
            'ACDF2' => $turno2?->ACDF2 ?? '',
            'ACDF3' => $turno2?->ACDF3 ?? '',
            'ACDF1_CARGO' => $turno2?->ACDF1_CARGO ?? '',
            'ACDF2_CARGO' => $turno2?->ACDF2_CARGO ?? '',
            'ACDF3_CARGO' => $turno2?->ACDF3_CARGO ?? '',
            'ACDJ1' => $turno2?->ACDJ1 ?? '',
            'ACDJ2' => $turno2?->ACDJ2 ?? '',
            'ACDJ3' => $turno2?->ACDJ3 ?? '',
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
            'turno1.TF1_CARGO' => ['nullable', 'string', 'max:255'],
            'turno1.TF2_CARGO' => ['nullable', 'string', 'max:255'],
            'turno1.TF3_CARGO' => ['nullable', 'string', 'max:255'],
            'semanaTurno1' => ['nullable', 'string', 'max:255'],
        ]);
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
            'turno2.ACDF1_CARGO' => ['nullable', 'string', 'max:255'],
            'turno2.ACDF2_CARGO' => ['nullable', 'string', 'max:255'],
            'turno2.ACDF3_CARGO' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ1' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ2' => ['nullable', 'string', 'max:255'],
            'turno2.ACDJ3' => ['nullable', 'string', 'max:255'],
            'periodoTurno2Acd' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function validateTurno3(): array
    {
        return $this->validate([
            'turno3.TM1' => ['nullable', 'string', 'max:255'],
            'turno3.TM2' => ['nullable', 'string', 'max:255'],
            'turno3.TM3' => ['nullable', 'string', 'max:255'],
        ])['turno3'];
    }

    private function turnoOneSummaryNotifier(): TurnoOneWeeklySummaryNotifier
    {
        return app(TurnoOneWeeklySummaryNotifier::class);
    }

    private function turnoTwoAcdSummaryNotifier(): TurnoTwoAcdWeeklySummaryNotifier
    {
        return app(TurnoTwoAcdWeeklySummaryNotifier::class);
    }

    private function normalizeSemanaTurno(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return (string) preg_replace('/^\s*semana\s+del\s+/i', '', $value);
    }
}
