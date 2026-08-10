<?php

namespace App\Livewire;

use App\Models\Audiencia;
use App\Models\TtyppRegistro;
use App\NotificationsV2\Application\Services\TtyppListadoNotifier;
use App\Services\FuncionarioCatalog;
use App\Services\SafeSpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

#[Layout('layouts.app')]
class TtyppPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public string $search = '';

    public array $selectedRegistroHistorial = [];

    public ?string $rit = null;

    public ?string $tipo_audiencia = null;

    public ?string $numero_dias = null;

    public ?string $cantidad = null;

    public ?string $modalidad = null;

    public ?string $encargada_ti_mp = null;

    public ?string $encargada_ti_d = null;

    public ?string $encargada_zoom = null;

    public ?string $ruc = null;

    public ?string $acta = null;

    public ?string $sala = null;

    public ?string $jueces = null;

    public ?string $fecha_asignacion = null;

    public $importExcel;

    public string $selectedDate = '';

    public function mount(): void
    {
        Gate::authorize('ttypp.ver');
        $this->selectedDate = $this->defaultFechaAsignacion();
        $this->fecha_asignacion = $this->defaultFechaAsignacion();
    }

    protected function rules(): array
    {
        return [
            'rit' => ['nullable', 'string', 'max:255'],
            'tipo_audiencia' => ['nullable', 'string', 'max:255'],
            'numero_dias' => ['nullable', 'string', 'max:50'],
            'cantidad' => ['nullable', 'string', 'max:255'],
            'modalidad' => ['nullable', 'string', 'max:255'],
            'encargada_ti_mp' => ['nullable', 'string', 'max:255'],
            'encargada_ti_d' => ['nullable', 'string', 'max:255'],
            'encargada_zoom' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:255'],
            'acta' => ['nullable', 'string', 'max:255'],
            'sala' => ['nullable', 'string', 'max:255'],
            'jueces' => ['nullable', 'string', 'max:255'],
            'fecha_asignacion' => ['nullable', 'date'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedDate(): void
    {
        $this->resetPage();
    }

    public function applySelectedDate(): void
    {
        $this->resetPage();
    }

    public function previousDay(): void
    {
        $date = Carbon::parse($this->selectedDate, 'America/Santiago')->subDay();
        $this->selectedDate = $date->toDateString();
        $this->resetPage();
    }

    public function nextDay(): void
    {
        $date = Carbon::parse($this->selectedDate, 'America/Santiago')->addDay();
        $this->selectedDate = $date->toDateString();
        $this->resetPage();
    }

    public function today(): void
    {
        $this->selectedDate = now('America/Santiago')->toDateString();
        $this->resetPage();
    }

    public function updatedRit(?string $value): void
    {
        $ruc = $this->rucForRit($value);

        if ($ruc) {
            $this->ruc = $ruc;
        }
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? 'ttypp.editar' : 'ttypp.crear');

        $validated = $this->validate();

        if ($this->editingId) {
            $registro = TtyppRegistro::query()->findOrFail($this->editingId);
            $payload = $this->sanitizePayload($validated);
            $payload['updated_by'] = Auth::id();
            $registro->update($payload);
            session()->flash('ok', 'Registro actualizado correctamente.');
        } else {
            $payload = $this->sanitizePayload($validated);
            $payload['created_by'] = Auth::id();
            $payload['updated_by'] = Auth::id();
            TtyppRegistro::query()->create($payload);
            session()->flash('ok', 'Registro creado correctamente.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        Gate::authorize('ttypp.editar');

        $registro = TtyppRegistro::query()->findOrFail($id);

        $this->editingId = $registro->id;
        $this->rit = $registro->rit;
        $this->tipo_audiencia = $registro->tipo_audiencia;
        $this->numero_dias = $registro->numero_dias;
        $this->cantidad = $registro->cantidad;
        $this->modalidad = $registro->modalidad;
        $this->encargada_ti_mp = $registro->encargada_ti_mp;
        $this->encargada_ti_d = $registro->encargada_ti_d;
        $this->encargada_zoom = $registro->encargada_zoom;
        $this->ruc = $registro->ruc;
        $this->acta = $registro->acta;
        $this->sala = $registro->sala;
        $this->jueces = $registro->jueces;
        $this->fecha_asignacion = optional($registro->fecha_asignacion)->format('Y-m-d');

        $this->selectedRegistroHistorial = $registro->historial()->with('user')->get()->toArray();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Gate::authorize('ttypp.editar');

        $registro = TtyppRegistro::query()->findOrFail($id);
        $registro->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('ok', 'Registro eliminado correctamente.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'rit',
            'tipo_audiencia',
            'numero_dias',
            'cantidad',
            'modalidad',
            'encargada_ti_mp',
            'encargada_ti_d',
            'encargada_zoom',
            'ruc',
            'acta',
            'sala',
            'jueces',
            'fecha_asignacion',
            'selectedRegistroHistorial',
        ]);

        $this->fecha_asignacion = $this->defaultFechaAsignacion();
    }

    private function defaultFechaAsignacion(): string
    {
        $today = Carbon::now('America/Santiago');

        if ($today->isFriday() || $today->isSaturday() || $today->isSunday()) {
            return $today->next(Carbon::MONDAY)->toDateString();
        }

        return $today->addDay()->toDateString();
    }

    public function importFromExcel(SafeSpreadsheetReader $reader): void
    {
        Gate::authorize('ttypp.importar');

        $this->validate([
            'importExcel' => ['required', File::types(['xlsx', 'xls'])->max(20 * 1024)],
        ]);

        $spreadsheet = $reader->load(
            $this->importExcel->getRealPath(),
            allowXls: true,
            maxRows: 5000,
            maxColumns: 7,
        );

        try {
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, true);

            if ($rows === []) {
                session()->flash('ok', 'El archivo Excel no contiene filas validas.');

                return;
            }

            $headers = array_shift($rows);

            if (! is_array($headers)) {
                session()->flash('ok', 'El archivo Excel no contiene encabezados validos.');

                return;
            }

            $normalizedHeaders = [];
            foreach ($headers as $column => $header) {
                $normalizedHeaders[$column] = $this->normalizeHeader((string) $header);
            }

            $read = 0;
            $inserted = 0;
            $filtered = 0;
            $invalid = 0;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    $invalid++;

                    continue;
                }

                $read++;
                $payload = $this->mapSiagjRowToPayload($normalizedHeaders, $row);

                if ($payload === null) {
                    $filtered++;

                    continue;
                }

                if ($this->isPayloadEmpty($payload) || blank($payload['rit'] ?? null) || blank($payload['fecha_asignacion'] ?? null)) {
                    $invalid++;

                    continue;
                }

                $payload['created_by'] = Auth::id();
                $payload['updated_by'] = Auth::id();

                TtyppRegistro::query()->create($payload);
                $inserted++;
            }

            $this->reset('importExcel');
            $this->resetPage();

            session()->flash('ok', "Importacion Excel completada. Filas leidas: {$read}. Filas insertadas: {$inserted}. Filas omitidas por filtro: {$filtered}. Filas invalidas: {$invalid}.");
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function exportExcel(): void
    {
        Gate::authorize('ttypp.exportar');

        $this->redirectRoute('ttypp.export', [
            'fecha' => $this->selectedDate,
            'search' => $this->search,
        ], navigate: false);
    }

    public function previewTtyppMail()
    {
        Gate::authorize('ttypp.mail.preview');

        return redirect()->route('preview.notifications-v2.ttypp-listado', [
            'fecha' => $this->selectedDate,
        ]);
    }

    public function sendTtyppMail(): void
    {
        Gate::authorize('ttypp.mail.enviar');

        $notifier = app(TtyppListadoNotifier::class);
        $registros = $notifier->recordsForDate($this->selectedDate);

        if ($registros->isEmpty()) {
            session()->flash('ok', 'No hay registros TTyPP para la fecha seleccionada. No se envio correo.');

            return;
        }

        if ($notifier->recipients() === []) {
            session()->flash('ok', 'No hay destinatarios TTyPP con correo valido en el catalogo de funcionarios.');

            return;
        }

        $notifier->notifyForDate($this->selectedDate, Auth::user()?->name ?? 'Usuario del sistema');

        session()->flash('ok', 'Correo TTyPP encolado/enviado correctamente.');
    }

    private function sanitizePayload(array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $clean[$key] = $value === '' ? null : $value;

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
        $value = str_replace(['.', '-', '_'], ' ', $value);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function normalizeValue(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
        $value = rtrim($value, '.');

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function mapSiagjRowToPayload(array $headers, array $row): ?array
    {
        $data = [];

        foreach ($headers as $column => $header) {
            if ($header !== '') {
                $data[$header] = $row[$column] ?? null;
            }
        }

        $estadoEvento = $this->normalizeValue($this->valueFor($data, ['estadoevento', 'estado evento']));
        $evento = $this->normalizeValue($this->valueFor($data, ['evento']));
        $tipoTramite = $this->normalizeValue($this->valueFor($data, ['tipo tramite', 'tipotramite']));

        if ($estadoEvento !== 'por realizarse' || $evento !== 'audiencia de juicio oral top' || $tipoTramite !== 'audiencia') {
            return null;
        }

        $rit = trim((string) $this->valueFor($data, ['rit']));
        $anioCausa = trim((string) $this->valueFor($data, ['anocausa', 'ano causa']));
        $fechaEvento = $this->parseExcelDate($this->valueFor($data, ['fechaevento', 'fecha evento']));
        $ritCompleto = $rit !== '' && $anioCausa !== '' ? $rit.'-'.$anioCausa : null;

        return $this->sanitizePayload([
            'rit' => $ritCompleto,
            'ruc' => $this->rucForRit($ritCompleto),
            'fecha_asignacion' => $fechaEvento,
        ]);
    }

    private function rucForRit(?string $rit): ?string
    {
        $rit = trim((string) $rit);

        if ($rit === '') {
            return null;
        }

        $ruc = Audiencia::query()
            ->where('rit', $rit)
            ->whereNotNull('ruc')
            ->where('ruc', '!=', '')
            ->latest('id')
            ->value('ruc');

        return $ruc ? (string) $ruc : null;
    }

    private function getAudienciaDataForRit(?string $rit): array
    {
        $rit = trim((string) $rit);
        if ($rit === '') {
            return [];
        }

        $audiencia = Audiencia::query()
            ->where('rit', $rit)
            ->latest('id')
            ->first();

        if (! $audiencia) {
            return [];
        }

        $jueces = collect([$audiencia->JuezP, $audiencia->JuezR, $audiencia->JuezI])
            ->filter(fn ($j) => ! empty(trim((string) $j)))
            ->implode(', ');

        return [
            'ruc' => $audiencia->ruc ? (string) $audiencia->ruc : null,
            'tipo_audiencia' => $audiencia->tipo_audiencia ? (string) $audiencia->tipo_audiencia : null,
            'acta' => $audiencia->acta ? (string) $audiencia->acta : null,
            'sala' => $audiencia->sala ? (string) $audiencia->sala : null,
            'jueces' => $jueces !== '' ? $jueces : null,
        ];
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $value = trim((string) $value);

        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value, 'America/Santiago');

                return $date->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value, 'America/Santiago')->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function valueFor(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return (string) $data[$key];
            }
        }

        return null;
    }

    private function isPayloadEmpty(array $payload): bool
    {
        foreach ($payload as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        $search = trim($this->search);
        $selectedDate = Carbon::parse($this->selectedDate, 'America/Santiago');
        $start = $selectedDate->copy()->startOfDay()->setTimezone('UTC');
        $end = $selectedDate->copy()->endOfDay()->setTimezone('UTC');

        $registrosQuery = TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end) {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end) {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            });

        // Obtener todos los registros del día para calcular métricas
        $registrosDia = (clone $registrosQuery)->get();
        $totalDia = $registrosDia->count();

        // Agrupación por modalidad para el dashboard
        $modalidadConteo = $registrosDia->groupBy('modalidad')
            ->map(fn ($group) => $group->count());

        // Conteo de causas con TTYPP completo (todas las encargadas asignadas) vs incompletos
        $ttyppAsignadas = $registrosDia->filter(function ($r) {
            return ! empty(trim((string) $r->encargada_ti_mp))
                && ! empty(trim((string) $r->encargada_ti_d))
                && ! empty(trim((string) $r->encargada_zoom));
        })->count();
        $ttyppPendientes = $totalDia - $ttyppAsignadas;

        $registros = $registrosQuery
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('rit', 'like', "%{$search}%")
                        ->orWhere('ruc', 'like', "%{$search}%")
                        ->orWhere('acta', 'like', "%{$search}%")
                        ->orWhere('sala', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(12);

        return view('livewire.ttypp-page', [
            'totalRegistros' => TtyppRegistro::query()->count(),
            'totalRegistrosDia' => $totalDia,
            'registros' => $registros,
            'encargadasTtypp' => app(FuncionarioCatalog::class)->nombresByRoles(['ttypp']),
            'actasSalaCausa' => app(FuncionarioCatalog::class)->nombresByRoles(['sala_causa']),

            // Dashboard Metrics
            'modalidadConteo' => $modalidadConteo,
            'ttyppAsignadas' => $ttyppAsignadas,
            'ttyppPendientes' => $ttyppPendientes,

            // Normalización Catálogos
            'modalidades' => ['Presencial+Zoom', 'Zoom', 'Presencial'],
        ]);
    }
}
