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
            // Según lo solicitado: cada "turno" toma TMx del registro id=2
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
        // AUDIENCIAS (vista de causas)
        // =========================
        $audiencias = Audiencia::query()
            ->whereDate('fecha', $fecha)
            ->orderBy('hora_inicio')
            ->get();

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

            // Inhabilitados: si viene vacío mostrará "NO"
            $inh = $asText($a->jueces_inhabilitados) ?: 'NO';

            // Segmento "Sala X y <zoom>" solo si hay algo que mostrar
            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            // Tipo de audiencia (fallback a "JUICIO ORAL")
            $tipo = $asText($a->tipo_audiencia) ?: 'JUICIO ORAL';

            // Identificadores causa (solo agrega los que existan)
            $rit = $asText($a->rit);
            $ruc = $asText($a->ruc);
            $segIds = implode(' - ', array_filter([
                $rit ? 'RIT ' . $rit : null,
                $ruc ? 'RUC ' . $ruc : null,
            ], fn ($v) => $v !== null && $v !== ''));

            // Duración (solo si existe)
            $duracion = $asText($a->duracion);
            $segDur = $duracion ? ('Duración ' . $duracion) : null;

            // Observación (solo si existe)
            $obs = $asText($a->obs);

            // Construimos partes sin guiones fijos
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

                // Normaliza acusados a arreglo indexado
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

            // Segmento "Sala X y <zoom>" solo si hay algo que mostrar
            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            // Partes del encabezado sin guiones fijos
            $partes = array_filter([
                $segSala ?: null,
                $hora ? "$hora Horas" : 'LECTURA DE SENTENCIA',
                'RIT ' . $asText($a->rit) . ' - RUC ' . $asText($a->ruc),
                $asText($a->obs) ?: null, // si no hay obs, no se agrega
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

            // Segmento "Sala X y <zoom>" solo si hay algo que mostrar
            $sala = $asText($a->sala);
            $zoom = $asText($a->cta_zoom);
            $segSala = implode(' y ', array_filter([
                $sala ? 'Sala ' . $sala : null,
                $zoom ?: null,
            ], fn ($v) => $v !== null && $v !== ''));

            // Identificadores causa (solo agrega los que existan)
            $rit = $asText($a->rit);
            $ruc = $asText($a->ruc);
            $segIds = implode(' - ', array_filter([
                $rit ? 'RIT ' . $rit : null,
                $ruc ? 'RUC ' . $ruc : null,
            ], fn ($v) => $v !== null && $v !== ''));

            // Observación (solo si existe)
            $obs = $asText($a->obs);

            // Encabezado sin guiones fijos
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

                // Normaliza acusados a arreglo indexado
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
