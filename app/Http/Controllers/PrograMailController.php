<?php

namespace App\Http\Controllers;

use App\Mail\ProgramacionDiariaHtmlMail;
use App\Exports\ProgramacionDiariaExport;
use App\Models\Audiencia;
use App\Models\Ausentismo;
use App\Models\Turno;
use App\Models\MailSignature;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Log;


class PrograMailController extends Controller
{
    /** ================== Helpers de normalización ================== */

    private function normalizeTipo(?string $tipo): string
    {
        $t = trim((string)$tipo);
        if (function_exists('mb_strtoupper')) {
            $t = mb_strtoupper($t, 'UTF-8');
            $t = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $t);
        } else {
            $t = strtoupper($t);
        }
        $t = preg_replace('/\s+/', ' ', $t ?? '');
        return trim($t ?? '');
    }

    private function shouldShowTipo(string $tipoNormalizado): bool
    {
        if ($tipoNormalizado === '') return false;

        $permitidos = [
            'JUICIO ORAL',
            'CONTINUACION JUICIO ORAL',
            'CONTINUACION DE JUICIO ORAL',
            'CONT. JUICIO ORAL',
            'CONT JUICIO ORAL',
        ];
        if (in_array($tipoNormalizado, $permitidos, true)) return true;

        return (bool) preg_match('/\b(CONT\.?\s*)?(DE\s*)?JUICIO\s+ORAL\b/u', $tipoNormalizado);
    }

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
            $nombre  = trim((string)($r->funcionario_nombre ?? ''));
            if (function_exists('mb_strtoupper')) {
                $nombre = mb_strtoupper($nombre, 'UTF-8');
            } else {
                $nombre = strtoupper($nombre);
            }
            $funcion = $r->observacion ?: $r->tipo_permiso;
            if ($funcion) {
                $funcion = function_exists('mb_strtoupper') ? mb_strtoupper($funcion, 'UTF-8') : strtoupper($funcion);
            } else {
                $funcion = '—';
            }
            return ['nombre' => $nombre !== '' ? $nombre : '—', 'funcion' => $funcion];
        })->values()->all();
    }

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

            // Hora segura
            $horaTxt = '—';
            if ($a->hora_inicio instanceof \DateTimeInterface) {
                $horaTxt = $a->hora_inicio->format('H:i') . ' Horas';
            } elseif (!empty($a->hora_inicio)) {
                try { $horaTxt = Carbon::parse($a->hora_inicio)->format('H:i') . ' Horas'; }
                catch (\Throwable $e) { $horaTxt = '—'; }
            }

            $mostrarTipo = $this->shouldShowTipo($tipoN);

            $encabezadoPartes = [
                sprintf('Sala %s y %s', (string)($a->sala ?? '—'), (string)($a->cta_zoom ?? '—')),
                $horaTxt,
                $mostrarTipo ? (string)($a->tipo_audiencia ?? null) : null,
                'RIT ' . ($a->rit ?? '—'),
                'RUC ' . ($a->ruc ?? '—'),
                $a->duracion ? ('Duración: ' . $a->duracion) : null,
                $a->obs ?: null,
            ];
            $encabezado = implode(' - ', array_values(array_filter($encabezadoPartes, static fn($v) => !is_null($v) && $v !== '')));

            $acusados = collect((array)($a->acusados ?? []))->map(static function ($ac) {
                $ac = (array)$ac;
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
                        ->map(static fn($i) => is_array($i) ? ($i['nombre_completo'] ?? null) : $i)
                        ->filter()->values()->implode(', '),
                    'juez_p'       => $a->JuezP ?? null,
                    'juez_r'       => $a->JuezR ?? null,
                    'juez_i'       => $a->JuezI ?? null,
                    'ttpp'         => $a->encargado_ttp ?? null,
                    'ttpp_zoom'    => $a->encargado_ttp_zoom ?? null,
                ]);
            } elseif (in_array($tipoN, ['LECTURA DE SENTENCIA','LECTURA SENTENCIA','LECTURA'], true)) {
                $lecturas[] = array_merge($common, ['juez_r' => $a->JuezR ?? null]);
            } else {
                $cortas[] = array_merge($common, [
                    'juez_p'   => $a->JuezP ?? null,
                    'juez_r'   => $a->JuezR ?? null,
                    'juez_i'   => $a->JuezI ?? null,
                    'anfitrion'=> $a->anfitrion ?? null,
                ]);
            }
        }

        // Turnos (null-safe)
        $turno  = Turno::find(2);
        $turno1 = $turno?->TM1 ?? null;
        $turno2 = $turno?->TM2 ?? null;
        $turno3 = $turno?->TM3 ?? null;

        // Jueces ausentes
        $juecesAusentes = $this->getJuecesAusentesFromDB($fechaYmd);

        return compact('juicios','lecturas','cortas','juecesAusentes','turno1','turno2','turno3');
    }

    /** ================== Helper de firma/mailer/from ================== */

    /**
     * Devuelve el HTML de firma, el mailer a usar y el from address para una firma.
     * - Si $firmaId es null o no existe, devuelve valores por defecto.
     */
    private function resolveSignatureConfig(?int $firmaId, string $fechaYmd): array
    {
        $firmaHtml = '';
        $mailerKey = config('mail.default');                          // mailer por defecto
        $fromAddr  = config('mail.from.address');                     // from por defecto
        $fromName  = "Programación Diaria 4°TOP (" . Carbon::parse($fechaYmd,'America/Santiago')->format('Y-m-d') . ")";

        if ($firmaId) {
            $firma = MailSignature::query()->where('activo', true)->find($firmaId);
            if ($firma) {
                $firmaHtml = (string)($firma->html ?? '');

                // mailer específico
                if (!empty($firma->mailer)) {
                    $mailerKey = trim((string)$firma->mailer);
                }

                // from específico desde env
                if (!empty($firma->from_env)) {
                    $envVal = env($firma->from_env);
                    if (!empty($envVal)) {
                        $fromAddr = $envVal;
                    }
                }
            }
        }

        return [$firmaHtml, $mailerKey, $fromAddr, $fromName];
    }

    /** ================== Envíos ================== */

    /** Envío rápido a un correo (o ?to=) — usa mailer/from si pasas ?firma_id= */
    public function enviarProgramacionPorCorreo(string $fecha, ?string $singleTo = null)
    {
        try {
            $data = $this->buildDataForMail($fecha);

            // Permite elegir firma por query param en este flujo también (?firma_id=123)
            $firmaId = request()->query('firma_id') ? (int) request()->query('firma_id') : null;
            [$firmaHtml, $mailerKey, $fromAddr, $fromName] = $this->resolveSignatureConfig($firmaId, $fecha);

            $mail = (new ProgramacionDiariaHtmlMail(
                $fecha,
                $data['juicios'],
                $data['lecturas'],
                $data['cortas'],
                $data['juecesAusentes'],
                $data['turno1'] ?? null,
                $data['turno2'] ?? null,
                $data['turno3'] ?? null,
                $firmaHtml
            ))->from($fromAddr, $fromName);

            // Adjunta Excel
            $excelBin = Excel::raw(new ProgramacionDiariaExport($fecha), ExcelFormat::XLSX);
            $mail->attachData($excelBin, "Programacion_{$fecha}.xlsx", [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);

            // Destinatarios
            $urlTo = request()->query('to');
            $to = $singleTo ?: $urlTo ?: null;
            $destinatarios = $to ? [trim($to)] : [config('mail.from.address')];
            $destinatarios = array_values(array_filter($destinatarios, static fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
            if (empty($destinatarios)) return back()->with('error', 'No hay destinatarios válidos para el envío.');

            // ¡Usa el mailer correcto!
            Mail::mailer($mailerKey)->to($destinatarios)->send($mail);
            
            // logs para ver envio de correo
            Log::info('Programación diaria enviada (rápida)', [
                'fecha'      => $fecha,
                'mailer'     => $mailerKey,
                'from'       => $fromAddr,
                'destinos'   => $destinatarios,
                'contexto'   => 'enviarProgramacionPorCorreo',
            ]);
            
            return back()->with('ok', "Programación {$fecha} enviada por correo (con adjunto).");
        } catch (\Throwable $ex) {
            Log::error('Fallo envío programación diaria (rápida)', [
                'fecha'    => $fecha,
                'mailer'   => $mailerKey ?? null,
                'from'     => $fromAddr ?? null,
                'error'    => $ex->getMessage(),
                'trace'    => $ex->getTraceAsString(),
                'contexto' => 'enviarProgramacionPorCorreo',
            ]);

            return back()->with('error', 'Fallo envío: '.$ex->getMessage());
        }
    }

    /** Envío a lista (To + BCC) con firma seleccionada desde el slider */
    public function enviarProgramacionPorCorreoLista(string $fecha, array $destinatarios, ?int $firmaId = null)
    {
        try {
            $destinatarios = array_values(array_unique(array_filter(
                $destinatarios,
                static fn($e) => filter_var(trim($e ?? ''), FILTER_VALIDATE_EMAIL)
            )));
            if (empty($destinatarios)) return back()->with('error','No hay destinatarios válidos.');

            $data = $this->buildDataForMail($fecha);

            // Resuelve firma/mailer/from
            [$firmaHtml, $mailerKey, $fromAddr, $fromName] = $this->resolveSignatureConfig($firmaId, $fecha);

            $mail = (new ProgramacionDiariaHtmlMail(
                $fecha,
                $data['juicios'],
                $data['lecturas'],
                $data['cortas'],
                $data['juecesAusentes'],
                $data['turno1'] ?? null,
                $data['turno2'] ?? null,
                $data['turno3'] ?? null,
                $firmaHtml
            ))->from($fromAddr, $fromName);

            // Adjunta Excel
            $excelBin = Excel::raw(new ProgramacionDiariaExport($fecha), ExcelFormat::XLSX);
            $mail->attachData($excelBin, "Programacion_{$fecha}.xlsx", [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);

            // To + BCC
            $to  = array_shift($destinatarios);
            $bcc = $destinatarios;

            $m = Mail::mailer($mailerKey)->to($to);
            if (!empty($bcc)) $m->bcc($bcc);
            $m->send($mail);
            
            //log para verificar envio de correo
            Log::info('Programación diaria enviada (lista)', [
                'fecha'      => $fecha,
                'mailer'     => $mailerKey,
                'from'       => $fromAddr,
                'to'         => $to,
                'bcc'        => $bcc,
                'total_dest' => 1 + count($bcc),
                'contexto'   => 'enviarProgramacionPorCorreoLista',
            ]);

            return back()->with('ok', "Programación {$fecha} enviada (difusión).");
        } catch (\Throwable $ex) {
            Log::error('Fallo envío programación diaria (lista)', [
                'fecha'    => $fecha,
                'mailer'   => $mailerKey ?? null,
                'from'     => $fromAddr ?? null,
                'to'       => $to ?? null,
                'bcc'      => $bcc ?? [],
                'error'    => $ex->getMessage(),
                'trace'    => $ex->getTraceAsString(),
                'contexto' => 'enviarProgramacionPorCorreoLista',
            ]);

            return back()->with('error', 'Fallo envío (lista): '.$ex->getMessage());
        }
    }

    /** ================== Vista previa navegador ================== */

    public function preview(?string $fecha = null)
    {
        $fecha = $fecha ?: Carbon::now('America/Santiago')->toDateString();
        $data = $this->buildDataForMail($fecha);

        return response()->view('emails.programacion-diaria-html', [
            'fechaHuman'     => Carbon::parse($fecha,'America/Santiago')->isoFormat('dddd D [de] MMMM YYYY'),
            'juicios'        => $data['juicios'],
            'lecturas'       => $data['lecturas'],
            'cortas'         => $data['cortas'],
            'juecesAusentes' => $data['juecesAusentes'],
            'turno1'         => $data['turno1'] ?? null,
            'turno2'         => $data['turno2'] ?? null,
            'turno3'         => $data['turno3'] ?? null,
            // Si quieres previsualizar firmas aquí, pasa 'firmaHtml' a la vista también
        ]);
    }
}
