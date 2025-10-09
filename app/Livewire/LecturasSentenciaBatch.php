<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LecturasSentenciaBatch extends Component
{
    /** Filas independientes */
    public array $items = [];

    /** Sugerencias */
    public array $situaciones = ['Libre', 'P.Prev.', 'P.x OC.'];

    public function mount(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        $this->addRow();
    }

    /** Prefill por RIT (opcional): trae últimos datos de esa causa */
    public function buscarPorRit(int $i): void
    {
        $rit = $this->items[$i]['rit'] ?? null;
        if (!$rit) {
            return;
        }

        $this->resetErrorBag("items.$i.rit");

        $aud = Audiencia::query()
            ->where('rit', $rit)
            ->latest('id')
            ->first();

        if (!$aud) {
            return;
        }

        // Rellena lo útil para LS (ajusta a gusto)
        $this->items[$i]['ruc']   = $this->items[$i]['ruc']   ?: ($aud->ruc ?? '');
        $this->items[$i]['acta']  = $this->items[$i]['acta']  ?: ($aud->acta ?? '');
        $this->items[$i]['obs']   = $this->items[$i]['obs']   ?: ($aud->obs ?? '');
        $this->items[$i]['hora_inicio']   = $this->items[$i]['hora_inicio']   ?: ($aud->hora_inicio ?? ''); 
        $this->items[$i]['encargado_sala']   = $this->items[$i]['encargado_sala']   ?: ($aud->encargado_causa ?? ''); 
        $this->items[$i]['JuezR']   = $this->items[$i]['JuezR']   ?: ($aud->JuezR ?? '');
        $this->items[$i]['sala']   = $this->items[$i]['sala']   ?: ($aud->sala ?? '');
        $this->items[$i]['cta_zoom']   = $this->items[$i]['cta_zoom']   ?: ($aud->cta_zoom ?? ''); 
        $this->items[$i]['acusados'] = is_array($aud->acusados) ? $aud->acusados : [];
    }

    public function addRow(): void
    {
        if (count($this->items) >= 5) {
            $this->dispatch('max-filas');
            return;
        }

        $this->items[] = [
            'fecha'          => now()->addDay()->toDateString(),
            'hora_inicio'    => '11:00',
            'JuezR'          => '',
            'tipo_audiencia' => 'Lectura de Sentencia',
            'sala'           => '',
            'cta_zoom'       => '',
            'rit'            => '',
            'ruc'            => '',
            'encargado_sala' => '',
            'acta'           => '',
            'obs'            => '',
            // Acusados
            'acusados'       => [],
            'nuevoAcusado'   => [
                'nombre_completo'    => '',
                'situacion'          => 'Libre',
                'medida_cautelar'    => '',
                'forma_notificacion' => '',
            ],
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function addAcusado(int $i): void
    {
        $this->validate([
            "items.$i.nuevoAcusado.nombre_completo" => 'required|string',
            "items.$i.nuevoAcusado.situacion"       => 'required|string',
        ]);

        $this->items[$i]['acusados'][] = $this->items[$i]['nuevoAcusado'];
        $this->items[$i]['nuevoAcusado'] = [
            'nombre_completo'    => '',
            'situacion'          => 'Libre',
            'medida_cautelar'    => '',
            'forma_notificacion' => '',
        ];
    }

    public function removeAcusado(int $i, int $k): void
    {
        unset($this->items[$i]['acusados'][$k]);
        $this->items[$i]['acusados'] = array_values($this->items[$i]['acusados']);
    }

    protected function rules(): array
    {
        return [
            'items'                            => 'required|array|min:1',
            'items.*.fecha'                    => 'required|date',
            'items.*.hora_inicio'              => 'required|date_format:H:i',
            'items.*.JuezR'                    => 'required|string',
            'items.*.tipo_audiencia'           => 'required|string',
            'items.*.sala'                     => 'required|string',
            'items.*.cta_zoom'                 => 'nullable|string',
            'items.*.rit'                      => 'required|string',
            'items.*.ruc'                      => 'required|string',
            'items.*.encargado_sala'           => 'required|string',
            'items.*.acta'                     => 'required|string',
            'items.*.obs'                      => 'nullable|string',
            'items.*.acusados'                 => 'required|array|min:1',
            'items.*.acusados.*.nombre_completo'    => 'required|string',
            'items.*.acusados.*.situacion'          => 'required|string',
            'items.*.acusados.*.medida_cautelar'    => 'nullable|string',
            'items.*.acusados.*.forma_notificacion' => 'nullable|string',
        ];
    }

    public function guardar(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('scroll-first-error');
            throw $e;
        }

        // Duplicados dentro del lote (RIT + fecha + tipo_audiencia)
        $seen = [];
        foreach ($this->items as $i => $row) {
            $key = implode('|', [$row['rit'], $row['fecha'], $row['tipo_audiencia']]);
            if (isset($seen[$key])) {
                $this->addError("items.$i.rit", "Duplicado con la fila ".($seen[$key] + 1)." (mismo RIT/fecha/tipo).");
            } else {
                $seen[$key] = $i;
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');
            return;
        }

        // Unicidad contra BD
        foreach ($this->items as $i => $row) {
            $exists = Audiencia::where('rit', $row['rit'])
                ->whereDate('fecha', $row['fecha'])
                ->where('tipo_audiencia', $row['tipo_audiencia'])
                ->exists();

            if ($exists) {
                $this->addError("items.$i.rit", "Ya existe una {$row['tipo_audiencia']} con este RIT en esa fecha.");
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');
            return;
        }

        DB::transaction(function () {
            foreach ($this->items as $row) {
                Audiencia::create([
                    'fecha'                => $row['fecha'],
                    'rit'                  => $row['rit'],
                    'sala'                 => $row['sala'],
                    // Si tu columna 'ubicacion' es NOT NULL, agrega el input o define un default aquí.
                    'ubicacion'            => null, // ⚠️ ajusta si tu esquema lo requiere
                    'hora_inicio'          => $row['hora_inicio'],
                    'ruc'                  => $row['ruc'],
                    'cta_zoom'             => $row['cta_zoom'] ?: null,
                    'tipo_audiencia'       => $row['tipo_audiencia'], // p.ej. "Lectura de Sentencia"
                    'num_testigos'         => null,
                    'num_peritos'          => null,
                    'duracion'             => null,
                    'delito'               => null,
                    'jueces_inhabilitados' => [],                   
                    'encargado_causa'      => $row['encargado_sala'],
                    'encargado_ttp'        => null,
                    'encargado_ttp_zoom'   => null,
                    'estado'               => 'POR_REALIZARSE',
                    'acusados'             => $row['acusados'],
                    'JuezP'                => null,
                    'JuezR'                => $row['JuezR'],
                    'JuezI'                => null,
                    'acta'                 => $row['acta'],
                    'obs'                  => $row['obs'],
                    'anfitrion'            => null,
                ]);
            }
        });

        // Reset: dejamos limpio y una fila nueva
        $this->items = [];
        $this->addRow();

        $this->dispatch('alerta-success');
    }

    public function render()
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        return view('livewire.lecturas-sentencia-batch');
    }
}
