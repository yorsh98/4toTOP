<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Turno;
use App\Models\Ausentismo;
use App\Models\Audiencia;

class PrograController extends Controller
{
    public function index(Request $request)
    {
        // =========================
        // Fecha (query ?fecha=YYYY-MM-DD) o hoy; TZ America/Santiago
        // =========================
        $tz = 'America/Santiago';
        $hoy = Carbon::now($tz);
        $fecha = $request->query('fecha');
        $fecha = $fecha ? Carbon::parse($fecha, $tz)->toDateString() : $hoy->toDateString();

        // =========================
        // TURNOS (USAR TM1, TM2, TM3 DEL REGISTRO id = 2)
        // =========================
        $turno1 = $turno2 = $turno3 = null;

        $turnoFila = Turno::query()->where('id', 2)->first();
        if ($turnoFila) {
            $turno1 = $turnoFila->TM1 ?? null; // TURNO 1
            $turno2 = $turnoFila->TM2 ?? null; // TURNO 2
            $turno3 = $turnoFila->TM3 ?? null; // TURNO 3
        }

        // =========================
        // JUECES AUSENTES (vigentes para la fecha)
        // =========================
        $juecesAusentes = Ausentismo::query()
            ->select(['funcionario_nombre','cargo','observacion','tipo_permiso','fecha_inicio','fecha_termino'])
            ->where(function ($q) {
                $q->where('cargo', 'LIKE', '%JUEZ%')
                  ->orWhere('cargo', 'LIKE', '%JUEZA%');
            })
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_termino')
                  ->orWhereDate('fecha_termino', '>=', $fecha);
            })
            ->orderBy('funcionario_nombre')
            ->get()
            ->map(function ($r) {
                return [
                    'nombre'  => mb_strtoupper($r->funcionario_nombre ?? ''),
                    'funcion' => $r->observacion ?: ($r->tipo_permiso ?: '—'),
                ];
            });

        // =========================
        // AUDIENCIAS (vista de causas) -> ORDEN POR SALA ASC y luego HORA
        // =========================
        $audiencias = Audiencia::query()
            ->whereDate('fecha', $fecha)
            ->get();

        // Normaliza y extrae el número de sala (ej. "Sala 801 (Zoom)" -> 801)
        $parseSala = function ($raw) {
            if ($raw === null) return null;
            if (preg_match('/\d{3,}/', (string)$raw, $m)) {
                return (int) $m[0];
            }
            return null;
        };

        $audiencias = $audiencias->sort(function ($a, $b) use ($parseSala) {
            $sa = $parseSala($a->sala);
            $sb = $parseSala($b->sala);

            // 1) Con sala primero (grupo 0), sin sala al final (grupo 1)
            $ga = is_null($sa) ? 1 : 0;
            $gb = is_null($sb) ? 1 : 0;
            if ($ga !== $gb) return $ga <=> $gb;

            // 2) Ambas con sala: orden numérico ascendente
            if ($ga === 0 && $sa !== $sb) return $sa <=> $sb;

            // 3) Desempate por hora ascendente (si existe)
            $ha = $a->hora_inicio ? (string)$a->hora_inicio : '';
            $hb = $b->hora_inicio ? (string)$b->hora_inicio : '';
            return strcmp($ha, $hb);
        })->values();

        // Helper para convertir arrays/objetos a string legible (evita "Array to string conversion")
        $asText = function ($v) {
            if (is_array($v)) {
                return implode(' — ', array_filter(array_map(function ($x) {
                    return is_array($x) ? implode(' ', $x) : (string) $x;
                }, $v)));
            }
            if ($v instanceof \DateTimeInterface) {
                return Carbon::parse($v)->toDateTimeString();
            }
            return $v === null ? null : (string) $v;
        };

        // Normalizador de tipo_audiencia -> grupos
        $tipoGrupo = function (?string $t) {
            $t = mb_strtoupper(trim($t ?? ''));
            if (str_contains($t, 'JUICIO'))  return 'juicio';
            if (str_contains($t, 'LECTURA')) return 'lectura';
            if (str_contains($t, 'CORTA'))   return 'corta';
            return $t === '' ? 'juicio' : 'corta';
        };

        // ---- Mapeos por tipo (conversión defensiva) ----
        $mapJuicio = function ($a) use ($asText) {
            $hora = $a->hora_inicio instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($a->hora_inicio)->format('H:i')
                : ($a->hora_inicio ? (string)$a->hora_inicio : null);

            $inh = $asText($a->jueces_inhabilitados) ?: 'NO';

            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            $tipo = $asText($a->tipo_audiencia) ?: 'JUICIO ORAL';

            $rit = $asText($a->rit);
            $ruc = $asText($a->ruc);
            $segIds = implode(' - ', array_filter([
                $rit ? 'RIT ' . $rit : null,
                $ruc ? 'RUC ' . $ruc : null,
            ], fn ($v) => $v !== null && $v !== ''));

            $duracion = $asText($a->duracion);
            $segDur = $duracion ? ('Duración ' . $duracion) : null;

            $obs = $asText($a->obs);

            $partes = array_filter([
                $segSala ?: null,
                $hora ? "$hora Horas" : null,
                $tipo,
                $segIds ?: null,
                $segDur,
                $obs ?: null,
            ], fn ($v) => $v !== null && $v !== '');

            return [
                'encabezado'   => implode(' - ', $partes),
                'num_testigos' => $asText($a->num_testigos),
                'num_peritos'  => $asText($a->num_peritos),
                'delito'       => $asText($a->delito),
                'inhabil'      => $inh,
                'encargado'    => $asText($a->encargado_causa),
                'juez_p'       => $asText($a->JuezP),
                'acta'         => $asText($a->acta),
                'juez_r'       => $asText($a->JuezR),
                'ttpp'         => $asText($a->encargado_ttp),
                'ttpp_zoom'    => $asText($a->encargado_ttp_zoom),
                'juez_i'       => $asText($a->JuezI),
                'zoom'         => $asText($a->cta_zoom),

                'acusados'     => collect($a->acusados ?? [])->map(function ($x) use ($asText) {
                    return [
                        'nombre'       => $asText($x['nombre_completo'] ?? ''),
                        'situacion'    => $asText($x['situacion'] ?? null),
                        'medidas'      => $asText($x['medida_cautelar'] ?? null),
                        'notificacion' => $asText($x['forma_notificacion'] ?? null),
                    ];
                })->values()->all(),
            ];
        };

        $mapLectura = function ($a) use ($asText) {
            $hora = $a->hora_inicio instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($a->hora_inicio)->format('H:i')
                : ($a->hora_inicio ? (string)$a->hora_inicio : null);

            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            $partes = array_filter([
                $segSala ?: null,
                $hora ? "$hora Horas" : 'LECTURA DE SENTENCIA',
                'RIT ' . $asText($a->rit) . ' - RUC ' . $asText($a->ruc),
                $asText($a->obs) ?: null,
            ], fn ($v) => $v !== null && $v !== '');

            return [
                'encabezado' => implode(' - ', $partes),
                'encargado'  => $asText($a->encargado_causa),
                'juez_r'     => $asText($a->JuezR),
                'acta'       => $asText($a->acta),
                'acusados'   => collect($a->acusados ?? [])->map(fn ($x) => [
                    'nombre' => $asText($x['nombre_completo'] ?? ''),
                ])->values()->all(),
            ];
        };

        $mapCorta = function ($a) use ($asText) {
            $hora = $a->hora_inicio instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($a->hora_inicio)->format('H:i')
                : ($a->hora_inicio ? (string)$a->hora_inicio : null);

            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            $rit = $asText($a->rit);
            $ruc = $asText($a->ruc);
            $segIds = implode(' - ', array_filter([
                $rit ? 'RIT ' . $rit : null,
                $ruc ? 'RUC ' . $ruc : null,
            ], fn ($v) => $v !== null && $v !== ''));

            $obs = $asText($a->obs);

            $partes = array_filter([
                $segSala ?: null,
                $hora ? "$hora Horas" : null,
                $segIds ?: null,
                $obs ?: null,
            ], fn ($v) => $v !== null && $v !== '');

            return [
                'encabezado' => implode(' - ', $partes),

                'anfitrion'  => $asText($a->anfitrion),
                'juez_p'     => $asText($a->JuezP),
                'acta'       => $asText($a->acta),
                'juez_r'     => $asText($a->JuezR),
                'encargado'  => $asText($a->encargado_causa),
                'juez_i'     => $asText($a->JuezI),
                'zoom'       => $asText($a->cta_zoom),

                'acusados'   => collect($a->acusados ?? [])->map(function ($x) use ($asText) {
                    return [
                        'nombre'    => $asText($x['nombre_completo'] ?? ''),
                        'situacion' => $asText($x['situacion'] ?? null),
                    ];
                })->values()->all(),
            ];
        };

        // Agrupación en el orden solicitado en la vista: Juicios -> Lecturas -> Cortas
        $juicios  = $audiencias->filter(fn($a) => $tipoGrupo($a->tipo_audiencia) === 'juicio')->map($mapJuicio)->values();
        $lecturas = $audiencias->filter(fn($a) => $tipoGrupo($a->tipo_audiencia) === 'lectura')->map($mapLectura)->values();
        $cortas   = $audiencias->filter(fn($a) => $tipoGrupo($a->tipo_audiencia) === 'corta')->map($mapCorta)->values();

        // Fecha legible
        $fechaHuman = Carbon::parse($fecha, $tz)->translatedFormat('j \\d\\e F Y');

        // Render
        return view('progra', compact(
            'fecha', 'fechaHuman',
            'turno1', 'turno2', 'turno3',
            'juicios', 'lecturas', 'cortas',
            'juecesAusentes'
        ));
    }
}
