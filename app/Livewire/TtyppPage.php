<?php

namespace App\Livewire;

use App\Models\TtyppRegistro;
use App\Models\Audiencia;
use App\Services\FuncionarioCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TtyppPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public string $search = '';

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

    public $importCsv;

    public string $selectedDate = '';

    public function mount(): void
    {
        Gate::authorize('ttypp.ver');
        $this->selectedDate = now('America/Santiago')->toDateString();
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
        $rit = trim((string) $value);

        if ($rit === '') {
            return;
        }

        $ruc = Audiencia::query()
            ->where('rit', $rit)
            ->whereNotNull('ruc')
            ->where('ruc', '!=', '')
            ->latest('id')
            ->value('ruc');

        if ($ruc) {
            $this->ruc = (string) $ruc;
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

    public function importFromCsv(): void
    {
        Gate::authorize('ttypp.importar');

        $this->validate([
            'importCsv' => ['required', File::types(['csv', 'txt'])->max(10 * 1024)],
        ]);

        $path = $this->importCsv->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            session()->flash('ok', 'No se pudo abrir el archivo CSV.');

            return;
        }

        $headers = fgetcsv($handle);

        if (! is_array($headers)) {
            fclose($handle);
            session()->flash('ok', 'El archivo CSV no contiene encabezados validos.');

            return;
        }

        $normalizedHeaders = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headers);

        $inserted = 0;
        $omitted = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (! is_array($row)) {
                $omitted++;
                continue;
            }

            $payload = $this->mapCsvRowToPayload($normalizedHeaders, $row);

            if ($this->isPayloadEmpty($payload)) {
                $omitted++;
                continue;
            }

            $payload['created_by'] = Auth::id();
            $payload['updated_by'] = Auth::id();

            TtyppRegistro::query()->create($payload);
            $inserted++;
        }

        fclose($handle);

        $this->reset('importCsv');
        $this->resetPage();

        session()->flash('ok', "Importacion completada. Filas insertadas: {$inserted}. Filas omitidas: {$omitted}.");
    }

    public function exportCsv()
    {
        Gate::authorize('ttypp.exportar');

        $filename = 'ttypp_'.now()->format('Ymd_His').'.csv';
        $search = trim($this->search);
        $selectedDate = Carbon::parse($this->selectedDate, 'America/Santiago');
        $start = $selectedDate->copy()->startOfDay()->setTimezone('UTC');
        $end = $selectedDate->copy()->endOfDay()->setTimezone('UTC');

        $rows = TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end) {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end) {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('rit', 'like', "%{$search}%")
                        ->orWhere('ruc', 'like', "%{$search}%")
                        ->orWhere('acta', 'like', "%{$search}%")
                        ->orWhere('sala', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['RIT', 'RUC', 'Tipo AUD', 'N Dias', 'Cant.', 'Modalidad', 'Encargada TI MP', 'Encargada TI D', 'Encargada Zoom', 'Acta', 'Sala', 'Jueces', 'Fecha Asignacion', 'Creado', 'Actualizado']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->rit,
                    $row->ruc,
                    $row->tipo_audiencia,
                    $row->numero_dias,
                    $row->cantidad,
                    $row->modalidad,
                    $row->encargada_ti_mp,
                    $row->encargada_ti_d,
                    $row->encargada_zoom,
                    $row->acta,
                    $row->sala,
                    $row->jueces,
                    optional($row->fecha_asignacion)->format('Y-m-d'),
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                    optional($row->updated_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
        $value = str_replace(['.', '-', '_'], ' ', $value);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function mapCsvRowToPayload(array $headers, array $row): array
    {
        $data = array_combine($headers, $row);

        if (! is_array($data)) {
            return [];
        }

        $payload = [
            'rit' => $this->valueFor($data, ['rit']),
            'ruc' => $this->valueFor($data, ['ruc']),
            'tipo_audiencia' => $this->valueFor($data, ['tipo aud', 'tipo audiencia']),
            'numero_dias' => $this->valueFor($data, ['n dias', 'numero dias', 'dias']),
            'cantidad' => $this->valueFor($data, ['cant', 'cant ttypp', 'cantidad']),
            'modalidad' => $this->valueFor($data, ['modalidad']),
            'encargada_ti_mp' => $this->valueFor($data, ['encargada ti mp', 'ti mp']),
            'encargada_ti_d' => $this->valueFor($data, ['encargada ti d', 'encargada ti defensa', 'ti d']),
            'encargada_zoom' => $this->valueFor($data, ['encargada zoom', 'zoom']),
            'acta' => $this->valueFor($data, ['acta']),
            'sala' => $this->valueFor($data, ['sala']),
            'jueces' => $this->valueFor($data, ['jueces', 'juez', 'jueza']),
            'fecha_asignacion' => $this->valueFor($data, ['fecha asignacion', 'fecha']),
        ];

        return $this->sanitizePayload($payload);
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

        $registros = TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end) {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end) {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
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
            'totalRegistrosDia' => TtyppRegistro::query()
                ->where(function ($query) use ($selectedDate, $start, $end) {
                    $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                        ->orWhere(function ($legacy) use ($start, $end) {
                            $legacy->whereNull('fecha_asignacion')
                                ->whereBetween('created_at', [$start, $end]);
                        });
                })
                ->count(),
            'registros' => $registros,
            'encargadasTtypp' => app(FuncionarioCatalog::class)->nombresByRoles(['ttypp']),
            'actasSalaCausa' => app(FuncionarioCatalog::class)->nombresByRoles(['sala_causa']),
        ]);
    }
}
