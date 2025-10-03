<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AudienciasCortasBatch extends Component
{
    /** Datos compartidos por todas las filas */
    public $shared = [
        'fecha'       => '',
        'sala'        => '',
        'JuezP'       => '',
        'JuezR'       => '',
        'JuezI'       => '',
        'anfitrion'   => '',
        'cta_zoom'    => '',
        'ubicacion'   => '',
        'hora_inicio' => '13:30',
    ];

    /** Filas a crear */
    public $items = [];

    /** Sugerencias */
    public array $situaciones = ['Libre', 'P.Prev.', 'P.x OC.'];

    /** Constante interna: no se expone en la vista */
    private const TIPO = 'Audiencia Corta';

    public function mount(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        $this->shared['fecha'] = now()->addDay()->toDateString();
        $this->addRow();
    }

    public function buscarPorRit(int $i): void
    {
        $rit   = $this->items[$i]['rit'] ?? null;
        $fecha = $this->shared['fecha'] ?? null;

        $this->resetErrorBag("items.$i.rit");

        if (!$rit) return;

        // Puedes filtrar por fecha/tipo si lo deseas; por ahora sólo por RIT (última coincidencia).
        $aud = Audiencia::query()
            ->where('rit', $rit)
            ->latest('id')
            ->first();

        if (!$aud) return;

        // Rellena SOLO campos de la fila (los compartidos no se tocan)
        $this->items[$i]['ruc']             = $aud->ruc ?? $this->items[$i]['ruc'];
        $this->items[$i]['encargado_causa'] = $aud->encargado_causa ?? $this->items[$i]['encargado_causa'];
        $this->items[$i]['acta']            = $aud->acta ?? $this->items[$i]['acta'];
        // NO copiamos tipo_audiencia desde BD: siempre será self::TIPO
        $this->items[$i]['obs']             = $aud->obs ?? $this->items[$i]['obs'];
        $this->items[$i]['acusados']        = is_array($aud->acusados) ? $aud->acusados : [];
    }

    public function addRow(): void
    {
        $this->items[] = [
            'rit'             => '',
            'ruc'             => '',
            'encargado_causa' => '',
            'acta'            => '',
            // 'tipo_audiencia' no se usa ni se muestra
            'obs'             => '',
            'acusados'        => [],
            'nuevoAcusado'    => [
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
            // Compartidos
            'shared.fecha'       => 'required|date',
            'shared.sala'        => 'required|string',
            'shared.JuezP'       => 'required|string',
            'shared.JuezR'       => 'required|string',
            'shared.JuezI'       => 'required|string',
            'shared.anfitrion'   => 'nullable|string',
            'shared.cta_zoom'    => 'nullable|string',
            'shared.ubicacion'   => 'required|string',
            'shared.hora_inicio' => 'required|date_format:H:i',

            // Filas
            'items'                        => 'required|array|min:1',
            'items.*.rit'                  => 'required|string',
            'items.*.ruc'                  => 'required|string',
            'items.*.encargado_causa'      => 'required|string',
            'items.*.acta'                 => 'required|string',
            'items.*.obs'                  => 'nullable|string',

            // Acusados (mínimo 1 por fila)
            'items.*.acusados'                     => 'required|array|min:1',
            'items.*.acusados.*.nombre_completo'   => 'required|string',
            'items.*.acusados.*.situacion'         => 'required|string',
            'items.*.acusados.*.medida_cautelar'   => 'nullable|string',
            'items.*.acusados.*.forma_notificacion'=> 'nullable|string',
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

        // Duplicados en el mismo lote (RIT repetido para la misma fecha)
        $seen = [];
        foreach ($this->items as $i => $row) {
            $key = $row['rit'].'|'.$this->shared['fecha'];
            if (isset($seen[$key])) {
                $this->addError("items.$i.rit", "Duplicado con la fila ".($seen[$key] + 1)." (mismo RIT en esa fecha).");
            } else {
                $seen[$key] = $i;
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');
            return;
        }

        // Unicidad contra BD: rit + fecha(compartida) + tipo_audiencia = 'Audiencia Corta'
        foreach ($this->items as $i => $row) {
            $exists = Audiencia::where('rit', $row['rit'])
                ->whereDate('fecha', $this->shared['fecha'])
                ->where('tipo_audiencia', self::TIPO)
                ->exists();

            if ($exists) {
                $this->addError("items.$i.rit", "Ya existe una Audiencia Corta con este RIT en esa fecha.");
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('scroll-first-error');
            return;
        }

        DB::transaction(function () {
            foreach ($this->items as $row) {
                Audiencia::create([
                    'fecha'                => $this->shared['fecha'],
                    'rit'                  => $row['rit'],
                    'sala'                 => $this->shared['sala'],
                    'ubicacion'            => $this->shared['ubicacion'],
                    'hora_inicio'          => $this->shared['hora_inicio'],
                    'ruc'                  => $row['ruc'],
                    'cta_zoom'             => $this->shared['cta_zoom'] ?: null,
                    'tipo_audiencia'       => self::TIPO, // <-- FORZADO AQUÍ
                    'num_testigos'         => null,
                    'num_peritos'          => null,
                    'duracion'             => null,
                    'delito'               => null,
                    'jueces_inhabilitados' => [],
                    'encargado_causa'      => $row['encargado_causa'],
                    'encargado_ttp'        => null,
                    'encargado_ttp_zoom'   => null,
                    'estado'               => 'POR_REALIZARSE',
                    'acusados'             => $row['acusados'], // $casts → JSON
                    'JuezP'                => $this->shared['JuezP'],
                    'JuezR'                => $this->shared['JuezR'],
                    'JuezI'                => $this->shared['JuezI'],
                    'acta'                 => $row['acta'],
                    'anfitrion'            => $this->shared['anfitrion'] ?: null,
                    'obs'                  => $row['obs'] ?: null,
                ]);
            }
        });

        // Reset suave: dejamos compartidos, limpiamos filas
        $this->items = [];
        $this->addRow();

        $this->dispatch('alerta-success');
    }

    public function render()
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        return view('livewire.audiencias-cortas-batch');
    }
}
