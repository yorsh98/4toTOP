<?php

namespace App\Livewire;

use App\Models\Audiencia;
use App\Services\FuncionarioCatalog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LecturasSentenciaBatch extends Component
{
    #[Locked]
    public ?int $editId = null;

    /** Filas independientes */
    public array $items = [];

    /** Sugerencias */
    #[Locked]
    public array $situaciones = ['Libre', 'P.Prev.', 'P.x OC.'];

    #[Locked]
    public array $juecesOptions = [];

    #[Locked]
    public array $actaOptions = [];

    #[Locked]
    public array $encargadoCausaOptions = [];

    public function mount(?int $edit = null): void
    {
        Gate::authorize($edit ? 'audiencias.editar' : 'audiencias.crear');
        $this->hydrateRoleOptions();

        if ($edit) {
            $this->loadForEdit($edit);

            return;
        }

        $this->addRow();
    }

    private function loadForEdit(int $id): void
    {
        $audiencia = Audiencia::query()
            ->whereKey($id)
            ->where('tipo_audiencia', 'Lectura de Sentencia')
            ->firstOrFail();

        $this->editId = $audiencia->id;
        $this->items = [[
            'fecha' => $audiencia->fecha->toDateString(),
            'hora_inicio' => $audiencia->hora_inicio?->format('H:i'),
            'JuezR' => $audiencia->JuezR,
            'tipo_audiencia' => $audiencia->tipo_audiencia,
            'sala' => $audiencia->sala,
            'cta_zoom' => $audiencia->cta_zoom,
            'rit' => $audiencia->rit,
            'ruc' => $audiencia->ruc,
            'encargado_sala' => $audiencia->encargado_causa,
            'acta' => $audiencia->acta,
            'obs' => $audiencia->obs,
            'acusados' => $audiencia->acusados ?? [],
            'nuevoAcusado' => [
                'nombre_completo' => '',
                'situacion' => 'Libre',
                'medida_cautelar' => '',
                'forma_notificacion' => '',
            ],
        ]];
    }

    public function hydrateRoleOptions(): void
    {
        $catalog = app(FuncionarioCatalog::class);

        $this->juecesOptions = $catalog->nombresByContext('audiencias.jueces');
        $this->actaOptions = $catalog->nombresByContext('audiencias.acta_anfitrion');
        $this->encargadoCausaOptions = $catalog->nombresByContext('audiencias.encargado_causa');
    }

    /** Prefill por RIT (opcional): trae últimos datos de esa causa */
    public function buscarPorRit(int $i): void
    {
        Gate::authorize('audiencias.ver');
        $rit = $this->items[$i]['rit'] ?? null;
        if (! $rit) {
            return;
        }

        $this->resetErrorBag("items.$i.rit");

        $aud = Audiencia::query()
            ->where('rit', $rit)
            ->latest('id')
            ->first();

        if (! $aud) {
            return;
        }

        // Rellena lo útil para LS (ajusta a gusto)
        $this->items[$i]['ruc'] = $this->items[$i]['ruc'] ?: ($aud->ruc ?? '');
        $this->items[$i]['acta'] = $this->items[$i]['acta'] ?: ($aud->acta ?? '');
        $this->items[$i]['obs'] = $this->items[$i]['obs'] ?: ($aud->obs ?? '');
        $this->items[$i]['hora_inicio'] = $this->items[$i]['hora_inicio'] ?: ($aud->hora_inicio ?? '');
        $this->items[$i]['encargado_sala'] = $this->items[$i]['encargado_sala'] ?: ($aud->encargado_causa ?? '');
        $this->items[$i]['JuezR'] = $this->items[$i]['JuezR'] ?: ($aud->JuezR ?? '');
        $this->items[$i]['sala'] = $this->items[$i]['sala'] ?: ($aud->sala ?? '');
        $this->items[$i]['cta_zoom'] = $this->items[$i]['cta_zoom'] ?: ($aud->cta_zoom ?? '');
        $this->items[$i]['acusados'] = is_array($aud->acusados) ? $aud->acusados : [];
    }

    public function addRow(): void
    {
        if (count($this->items) >= 5) {
            $this->dispatch('max-filas');

            return;
        }

        $this->items[] = [
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '11:00',
            'JuezR' => '',
            'tipo_audiencia' => 'Lectura de Sentencia',
            'sala' => '',
            'cta_zoom' => '',
            'rit' => '',
            'ruc' => '',
            'encargado_sala' => '',
            'acta' => '',
            'obs' => '',
            // Acusados
            'acusados' => [],
            'nuevoAcusado' => [
                'nombre_completo' => '',
                'situacion' => 'Libre',
                'medida_cautelar' => '',
                'forma_notificacion' => '',
            ],
        ];
    }

    public function removeRow(int $index): void
    {
        // En edición la primera fila es la audiencia abierta; se elimina desde la tabla.
        if ($this->editId !== null && $index === 0) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function addAcusado(int $i): void
    {
        if (! isset($this->items[$i]['nuevoAcusado'])) {
            return;
        }

        $this->validate([
            "items.$i.nuevoAcusado.nombre_completo" => 'required|string|max:255',
            "items.$i.nuevoAcusado.situacion" => 'required|string|max:255',
            "items.$i.nuevoAcusado.medida_cautelar" => 'nullable|string|max:255',
            "items.$i.nuevoAcusado.forma_notificacion" => 'nullable|string|max:255',
        ]);

        $this->items[$i]['acusados'][] = $this->items[$i]['nuevoAcusado'];
        $this->items[$i]['nuevoAcusado'] = [
            'nombre_completo' => '',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ];
    }

    public function removeAcusado(int $i, int $k): void
    {
        if (! isset($this->items[$i]['acusados'][$k])) {
            return;
        }

        unset($this->items[$i]['acusados'][$k]);
        $this->items[$i]['acusados'] = array_values($this->items[$i]['acusados']);
    }

    protected function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:5',
            'items.*.fecha' => 'required|date_format:Y-m-d',
            'items.*.hora_inicio' => 'required|date_format:H:i',
            'items.*.JuezR' => ['required', 'string', 'max:255'],
            'items.*.sala' => 'required|string|max:255',
            'items.*.cta_zoom' => 'nullable|string|max:255',
            'items.*.rit' => 'required|string|max:255',
            'items.*.ruc' => 'required|string|max:255',
            'items.*.encargado_sala' => ['required', 'string', 'max:255'],
            'items.*.acta' => ['required', 'string', 'max:255'],
            'items.*.obs' => 'nullable|string|max:5000',
            'items.*.acusados' => 'required|array|min:1|max:100',
            'items.*.acusados.*.nombre_completo' => 'required|string|max:255',
            'items.*.acusados.*.situacion' => 'required|string|max:255',
            'items.*.acusados.*.medida_cautelar' => 'nullable|string|max:255',
            'items.*.acusados.*.forma_notificacion' => 'nullable|string|max:255',
        ];
    }

    public function guardar(): void
    {
        if ($this->editId === null) {
            Gate::authorize('audiencias.crear');
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('scroll-first-error');
            throw $e;
        }

        // Duplicados dentro del lote (RIT + fecha + tipo_audiencia)
        $seen = [];
        foreach ($this->items as $i => $row) {
            $key = implode('|', [$row['rit'], $row['fecha'], 'Lectura de Sentencia']);
            if (isset($seen[$key])) {
                $this->addError("items.$i.rit", 'Duplicado con la fila '.($seen[$key] + 1).' (mismo RIT/fecha/tipo).');
            } else {
                $seen[$key] = $i;
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');

            return;
        }

        if (count($this->items) > ($this->editId === null ? 0 : 1)) {
            Gate::authorize('audiencias.crear');
        }

        // Unicidad contra BD
        foreach ($this->items as $i => $row) {
            $exists = Audiencia::where('rit', $row['rit'])
                ->whereDate('fecha', $row['fecha'])
                ->where('tipo_audiencia', 'Lectura de Sentencia')
                ->when($this->editId !== null && $i === 0, fn ($q) => $q->whereKeyNot($this->editId))
                ->exists();

            if ($exists) {
                $this->addError("items.$i.rit", 'Ya existe una Lectura de Sentencia con este RIT en esa fecha.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');

            return;
        }

        DB::transaction(function () {
            foreach ($this->items as $i => $row) {
                if ($this->editId !== null && $i === 0) {
                    Gate::authorize('audiencias.editar');
                    Audiencia::query()->where('tipo_audiencia', 'Lectura de Sentencia')->findOrFail($this->editId)->update(Arr::except($this->audienciaData($row), 'estado'));
                } else {
                    Audiencia::create($this->audienciaData($row));
                }
            }
        });

        $fechas = collect($this->items)->pluck('fecha')->unique()->values();

        // Reset: dejamos limpio y una fila nueva
        $this->items = [];
        $this->addRow();

        $this->dispatch('alerta-success', fecha: $fechas->map(fn ($fecha) => Carbon::parse($fecha)->format('d-m-Y'))->join(', '));
        $this->dispatch('audiencia-guardada', fecha: $fechas->first());
    }

    private function audienciaData(array $row): array
    {
        return [
            'fecha' => $row['fecha'],
            'rit' => $row['rit'],
            'sala' => $row['sala'],
            'ubicacion' => null,
            'hora_inicio' => $row['hora_inicio'],
            'ruc' => $row['ruc'],
            'cta_zoom' => $row['cta_zoom'] ?: null,
            'tipo_audiencia' => 'Lectura de Sentencia',
            'num_testigos' => null,
            'num_peritos' => null,
            'duracion' => null,
            'delito' => null,
            'jueces_inhabilitados' => [],
            'encargado_causa' => $row['encargado_sala'],
            'encargado_ttp' => null,
            'encargado_ttp_zoom' => null,
            'estado' => 'POR_REALIZARSE',
            'acusados' => $row['acusados'],
            'JuezP' => null,
            'JuezR' => $row['JuezR'],
            'JuezI' => null,
            'acta' => $row['acta'],
            'obs' => $row['obs'],
            'anfitrion' => null,
        ];
    }

    public function render()
    {
        Gate::authorize('audiencias.ver');

        return view('livewire.lecturas-sentencia-batch');
    }
}
