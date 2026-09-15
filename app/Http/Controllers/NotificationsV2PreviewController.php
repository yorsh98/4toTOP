<?php

namespace App\Http\Controllers;

use App\NotificationsV2\Application\Services\TtyppListadoNotifier;
use App\NotificationsV2\Application\Services\TurnoOneWeeklySummaryNotifier;
use App\NotificationsV2\Application\Services\TurnoTwoAcdWeeklySummaryNotifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsV2PreviewController extends Controller
{
    public function turnoAsignado(Request $request, TurnoOneWeeklySummaryNotifier $notifier): View
    {
        $date = $request->query('date');
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = null;
        }

        $summary = $notifier->buildWeekSummaryData(is_string($date) && trim($date) !== '' ? $date : null);
        $payload = $summary['payload'];

        $funcionarios = $payload['funcionarios'] ?? [];
        if (! is_array($funcionarios)) {
            $funcionarios = [];
        }

        $juezDespacho = $payload['juez_despacho'] ?? [];
        if (! is_array($juezDespacho)) {
            $juezDespacho = [];
        }

        return view('emails.v2.turno-asignado-outlook', [
            'fechaTurno' => (string) ($payload['fecha_turno'] ?? now()->toDateString()),
            'semanaRango' => (string) ($payload['semana_rango'] ?? now()->toDateString()),
            'tribunalOral' => (string) ($payload['tribunal_oral'] ?? '4to TOP'),
            'juezDespacho' => array_merge([
                'run' => '-',
                'nombre' => '-',
                'telefono' => '-',
                'correo' => '-',
                'cargo' => 'Juez',
            ], $juezDespacho),
            'jefeTurno' => array_merge([
                'run' => '-',
                'nombre' => '-',
                'telefono' => '-',
                'correo' => '-',
                'cargo' => 'Jefe de Turno',
            ], is_array($payload['jefe_turno'] ?? null) ? $payload['jefe_turno'] : []),
            'funcionarios' => $funcionarios,
        ]);
    }

    public function turno2Acd(Request $request, TurnoTwoAcdWeeklySummaryNotifier $notifier): View
    {
        $date = $request->query('date');
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = null;
        }

        $summary = $notifier->buildWeekSummaryData(is_string($date) && trim($date) !== '' ? $date : null);
        $payload = $summary['payload'];

        $magistrados = $payload['magistrados'] ?? [];
        if (! is_array($magistrados)) {
            $magistrados = [];
        }

        $funcionarios = $payload['funcionarios'] ?? [];
        if (! is_array($funcionarios)) {
            $funcionarios = [];
        }

        return view('emails.v2.turno2-acd-resumen-outlook', [
            'semanaRango' => (string) ($payload['semana_rango'] ?? now()->toDateString()),
            'tribunalOral' => (string) ($payload['tribunal_oral'] ?? '4to TOP'),
            'magistrados' => $magistrados,
            'jefatura' => [
                'run' => (string) ($payload['jefatura_run'] ?? '-'),
                'nombre' => (string) ($payload['jefatura_nombre'] ?? '-'),
                'telefono' => (string) ($payload['jefatura_telefono'] ?? '-'),
                'correo' => (string) ($payload['jefatura_correo'] ?? '-'),
                'cargo' => (string) ($payload['jefatura_observaciones'] ?? 'Jefe de Turno'),
            ],
            'funcionarios' => $funcionarios,
            'tribunalRowspan' => (int) ($payload['tribunal_rowspan'] ?? 7),
        ]);
    }

    public function ausentismoResumen(Request $request): View
    {
        $empty = $request->boolean('empty', false);

        $ausentes = $empty ? [] : [
            [
                'funcionario_nombre' => 'Funcionario Uno',
                'cargo' => 'Juez/a',
                'tipo_permiso' => 'Licencia Medica',
                'fecha_inicio' => now()->format('Y-m-d'),
                'fecha_termino' => now()->addDay()->format('Y-m-d'),
                'observacion' => 'Control medico',
            ],
            [
                'funcionario_nombre' => 'Funcionario Dos',
                'cargo' => 'Administrativo/a',
                'tipo_permiso' => 'Feriado Legal',
                'fecha_inicio' => now()->format('Y-m-d'),
                'fecha_termino' => now()->addDays(2)->format('Y-m-d'),
                'observacion' => 'Vacaciones',
            ],
        ];

        return view('emails.v2.ausentismo-resumen-diario-outlook', [
            'fechaReporte' => now()->format('Y-m-d'),
            'ausentes' => $ausentes,
        ]);
    }

    public function userStatus(Request $request): View
    {
        $status = (string) $request->query('status', 'approved');

        [$subject, $line] = match ($status) {
            'approved' => [
                'Tu cuenta fue aprobada',
                'Tu cuenta ya fue aprobada y puedes iniciar sesion en el sistema.',
            ],
            'rejected' => [
                'Tu cuenta fue rechazada',
                'Tu solicitud de acceso fue rechazada por un administrador.',
            ],
            'suspended' => [
                'Tu cuenta fue suspendida',
                'Tu cuenta fue suspendida y no podras ingresar hasta que sea reactivada.',
            ],
            'unsuspended' => [
                'Tu cuenta fue reactivada',
                'Tu cuenta fue reactivada y ya puedes volver a ingresar al sistema.',
            ],
            default => [
                'Actualizacion de cuenta',
                'Se actualizo el estado de tu cuenta en el sistema.',
            ],
        };

        return view('emails.v2.legacy-user-status-outlook', [
            'subject' => $subject,
            'line' => $line,
            'userName' => 'Usuario Demo',
            'adminName' => 'Admin Demo',
        ]);
    }

    public function bitacoraPruebas(Request $request): View
    {
        return view('emails.v2.bitacora-pruebas-outlook', [
            'subject' => 'Notificacion de Bitacora de Pruebas',
            'senderName' => (string) $request->query('sender', 'Admin Demo'),
            'documentosUrl' => (string) $request->query('documentos', 'https://example.com/documentos'),
            'bitacoraPublicUrl' => (string) $request->query('bitacora', route('bitacora-pruebas.public')),
            'recipientName' => (string) $request->query('user', 'Usuario Demo'),
        ]);
    }

    public function ttyppListado(Request $request, TtyppListadoNotifier $notifier): View
    {
        $date = $request->query('fecha');
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now('America/Santiago')->toDateString();
        }

        return view('emails.v2.ttypp-listado-outlook', [
            'fecha' => $date,
            'senderName' => $request->user()?->name ?? 'Usuario Demo',
            'registros' => $notifier->recordsForDate($date),
        ]);
    }
}
