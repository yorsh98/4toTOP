<?php

namespace App\Http\Controllers;

use App\Mail\ProgramacionDiariaHtmlMail;
use App\Exports\ProgramacionDiariaExport;
use App\Models\Audiencia;
use App\Models\Ausentismo;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class PrograMailController extends Controller
{
    /** Normaliza tipo de audiencia (como en tu export) */
    private function normalizeTipo(?string $tipo): string
    {
        $t = mb_strtoupper(trim((string)$tipo), 'UTF-8');
        // quita tildes comunes
        $t = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $t);
        // normaliza algunos puntos y espacios
        $t = preg_replace('/\s+/', ' ', $t);
        return trim($t);
    }

    /** ¿Debemos mostrar el tipo en el encabezado? (solo Juicio Oral o su continuación) */
    private function shouldShowTipo(string $tipoNormalizado): bool
    {
        if ($tipoNormalizado === '') return false;

        // Coincidencias exactas más comunes
        $permitidos = [
            'JUICIO ORAL',
            'CONTINUACION JUICIO ORAL',
            'CONTINUACION DE JUICIO ORAL',
            'CONT. JUICIO ORAL',
            'CONT JUICIO ORAL',
        ];
        if (in_array($tipoNormalizado, $permitidos, true)) {
            return true;
        }

        // Respaldo por regex (por si llega alguna variante similar)
        if (preg_match('/\b(CONT\.?\s*)?(DE\s*)?JUICIO\s+ORAL\b/u', $tipoNormalizado)) {
            return true;
        }

        return false;
    }

    /** Jueces ausentes (misma lógica base que tu export) */
    private function getJuecesAusentesFromDB(string $fecha): array
    {
        $rows = Ausentismo::query()
            ->select(['funcionario_nombre','cargo','observacion','tipo_permiso'])
            ->where('cargo', 'LIKE', '%Juez/a%')
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_termino')
                  ->orWhereDate('fecha_termino', '>=', $fecha);
            })
            ->orderBy('funcionario_nombre')
            ->get();

        return $rows->map(function ($r) {
            $nombre  = mb_strtoupper(trim((string)($r->funcionario_nombre ?? '')), 'UTF-8');
            $funcion = $r->observacion ?: $r->tipo_permiso;
            $funcion = $funcion ? mb_strtoupper($funcion, 'UTF-8') : '—';
            return ['nombre' => $nombre !== '' ? $nombre : '—', 'funcion' => $funcion];
        })->values()->all();
    }

    /** Arma arrays para la vista HTML del correo */
    private function buildDataForMail(string $fechaYmd): array
    {
        $audiencias = Audiencia::query()
            ->whereDate('fecha', $fechaYmd)
            ->orderBy('sala')
            ->orderBy('hora_inicio')
            ->get();

        $juicios = [];
        $lecturas = [];
        $cortas = [];

        foreach ($audiencias as $a) {
            $tipoN = $this->normalizeTipo($a->tipo_audiencia ?? '');

            // Hora segura (evita parsear null)
            $horaTxt = '—';
            if ($a->hora_inicio instanceof \DateTimeInterface) {
                $horaTxt = $a->hora_inicio->format('H:i') . ' Horas';
            } elseif (!empty($a->hora_inicio)) {
                try {
                    $horaTxt = Carbon::parse($a->hora_inicio)->format('H:i') . ' Horas';
                } catch (\Throwable $e) {
                    $horaTxt = '—';
                }
            }

            // Solo mostrar tipo si es Juicio Oral / Continuación Juicio Oral
            $mostrarTipo = $this->shouldShowTipo($tipoN);

            $encabezadoPartes = [
                sprintf('Sala %s y %s', (string)($a->sala ?? '—'), (string)($a->cta_zoom ?? '—')),
                $horaTxt,
                $mostrarTipo ? (string)($a->tipo_audiencia ?? null) : null, // <- regla aplicada
                'RIT ' . ($a->rit ?? '—'),
                'RUC ' . ($a->ruc ?? '—'),
                $a->duracion ? ('Duración: ' . $a->duracion) : null,
                $a->obs ?: null,
            ];
            // limpia vacíos y une
            $encabezado = implode(' - ', array_values(array_filter($encabezadoPartes, fn($v) => !is_null($v) && $v !== '')));

            // mapea acusados (según tu export)
            $acusados = collect((array)($a->acusados ?? []))->map(function ($ac) {
                return [
                    'nombre'        => $ac['nombre_completo'] ?? ($ac['nombre'] ?? '—'),
                    'situacion'     => $ac['situacion'] ?? ($ac['situacion_libertad'] ?? null),
                    'medidas'       => $ac['medida_cautelar'] ?? ($ac['medidas'] ?? null),
                    'notificacion'  => $ac['forma_notificacion'] ?? ($ac['notificacion'] ?? null),
                ];
            })->values()->all();

            $common = [
                'encabezado' => $encabezado,
                'acta'       => $a->acta ?? null,
                'encargado'  => $a->encargado_causa ?? null,
                'acusados'   => $acusados,
            ];

            if (in_array($tipoN, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACION DE JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)) {
                $juicios[] = array_merge($common, [
                    'delito'       => $a->delito ?? null,
                    'num_testigos' => $a->num_testigos ?? null,
                    'num_peritos'  => $a->num_peritos ?? null,
                    'inhabil'      => collect($a->jueces_inhabilitados ?? [])
                                        ->map(fn($i) => is_array($i) ? ($i['nombre_completo'] ?? null) : $i)
                                        ->filter()->values()->implode(', '),
                    'juez_p'       => $a->JuezP ?? null,
                    'juez_r'       => $a->JuezR ?? null,
                    'juez_i'       => $a->JuezI ?? null,
                    'ttpp'         => $a->encargado_ttp ?? null,
                    'ttpp_zoom'    => $a->encargado_ttp_zoom ?? null,
                ]);
            } elseif (in_array($tipoN, ['LECTURA DE SENTENCIA','LECTURA SENTENCIA','LECTURA'], true)) {
                $lecturas[] = array_merge($common, [
                    'juez_r' => $a->JuezR ?? null,
                ]);
            } else { // AUDIENCIA CORTA u otros
                $cortas[] = array_merge($common, [
                    'juez_p'   => $a->JuezP ?? null,
                    'juez_r'   => $a->JuezR ?? null,
                    'juez_i'   => $a->JuezI ?? null,
                    'anfitrion'=> $a->anfitrion ?? null,
                ]);
            }
        }

        // Turnos (ajusta según tu lógica real)
        $turno = Turno::find(2); // según tu export, usas id=2 como base
        $turno1 = $turno->TM1 ?? null;
        $turno2 = $turno->TM2 ?? null;
        $turno3 = $turno->TM3 ?? null;

        // Jueces ausentes
        $juecesAusentes = $this->getJuecesAusentesFromDB($fechaYmd);

        return compact('juicios','lecturas','cortas','juecesAusentes','turno1','turno2','turno3');
    }

    /** Acción pública que envía el correo + adjunta tu Excel */
    public function enviarProgramacionPorCorreo(string $fecha)
    {
        // 1) Datos para el cuerpo HTML
        $data = $this->buildDataForMail($fecha);

        // 2) Arma el mailable con tu vista HTML
        $fromName = "Programación Diaria 4°TOP (" . Carbon::parse($fecha,'America/Santiago')->format('Y-m-d') . ")";
        $mail = (new ProgramacionDiariaHtmlMail(
            $fecha,
            $data['juicios'],
            $data['lecturas'],
            $data['cortas'],
            $data['juecesAusentes'],
            $data['turno1'],
            $data['turno2'],
            $data['turno3'],
        ))->from(env('MAIL_FROM_ADDRESS'), $fromName);

        // 3) Adjunta el Excel usando tu export
        $excelBin = Excel::raw(new ProgramacionDiariaExport($fecha), ExcelFormat::XLSX);
        $mail->attachData(
            $excelBin,
            "Programacion_{$fecha}.xlsx",
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        // 4) Destinatarios (ajusta)
        $destinatarios = ['jorge.troncoso4@gmail.com'];

        // 5) Enviar (puedes usar ->queue si tienes worker)
        Mail::to($destinatarios)->send($mail);

        return back()->with('ok', "Programación {$fecha} enviada por correo (con adjunto).");
    }

    /** Vista previa en el navegador usando la misma vista del correo */
    public function preview(?string $fecha = null)
    {
        $fecha = $fecha ?: Carbon::now('America/Santiago')->toDateString();

        // Usa la MISMA fuente de datos que el envío real
        $data = $this->buildDataForMail($fecha);

        // Renderiza la MISMA vista del correo
        return response()->view('emails.programacion-diaria-html', [
            'fechaHuman'     => Carbon::parse($fecha,'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY'),
            'juicios'        => $data['juicios'],
            'lecturas'       => $data['lecturas'],
            'cortas'         => $data['cortas'],
            'juecesAusentes' => $data['juecesAusentes'],
            'turno1'         => $data['turno1'] ?? null,
            'turno2'         => $data['turno2'] ?? null,
            'turno3'         => $data['turno3'] ?? null,
        ]);
    }
}
