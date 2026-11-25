<?php

namespace App\NotificationsV2\Support;

use App\NotificationsV2\Domain\Models\NotificationType;
use Illuminate\Support\Facades\DB;

/**
 * Plantillas de correo pensadas para Outlook 365 de escritorio (motor de Word).
 *
 * El diseño usa tablas
 * anidadas con ancho fijo, estilos en línea y botones VML; el bloque <style> solo
 * aporta tipografía base a los fragmentos HTML que arman los notifiers.
 */
final class OutlookEmailTemplates
{
    private const DATA_TYPOGRAPHY = 'font-family:'.self::FONT.';font-size:12px;line-height:16px;mso-line-height-rule:exactly;font-weight:normal;color:#1F2937;';

    private const MARKER = '<!-- 4totop-outlook-v1 -->';

    private const NAVY = '#1F3864';

    private const FONT = 'Arial,Helvetica,sans-serif';

    /** @var array<string, array{bar: string, text: string, soft: string}> */
    private const ACCENTS = [
        'blue' => ['bar' => '#2F5597', 'text' => '#2F5597', 'soft' => '#EAF0F9'],
        'amber' => ['bar' => '#C77F0A', 'text' => '#8A5300', 'soft' => '#FFF4DC'],
        'red' => ['bar' => '#9B2C2C', 'text' => '#9B2C2C', 'soft' => '#FBECEC'],
    ];

    public static function isOutlook(string $html): bool
    {
        return str_contains($html, self::MARKER);
    }

    public static function ensureActive(NotificationType $type): void
    {
        DB::transaction(function () use ($type): void {
            NotificationType::query()->whereKey($type->getKey())->lockForUpdate()->firstOrFail();

            $active = $type->templates()->where('is_active', true)->orderByDesc('version')->first();

            if ($active && self::isOutlook($active->body_html)) {
                return;
            }

            $template = self::forType($type->code);
            $type->templates()->where('is_active', true)->update(['is_active' => false]);
            $type->templates()->create([
                ...$template,
                'subject' => $active?->subject ?? $template['subject'],
                'version' => $type->templates()->max('version') + 1,
                'is_active' => true,
                'activated_at' => now(),
            ]);
        });
    }

    public static function forType(string $code): array
    {
        [$subject, $html, $text, $variables] = match ($code) {
            'ausentismo.resumen_diario' => [
                'Resumen diario de ausentismo - {{ fecha_reporte }}', self::ausentismoResumenHtml(), self::ausentismoResumenText(),
                [
                    'fecha_reporte',
                    'total_ausentes',
                    'ausentes',
                    'ausentes_html',
                    'ausentes_text',
                    'mensaje_sin_novedades',
                ],
            ],
            'visita_carcel.revision_pp' => [
                'Revisión de PP - {{ fecha }}', self::visitaCarcelRevisionPpHtml(), self::visitaCarcelRevisionPpText(),
                ['fecha', 'total_imputados', 'tabla_rows_html', 'tabla_rows_text', 'mensaje_sin_imputados'],
            ],
            'ttypp.listado.diario' => [
                '{{ subject }}', self::ttyppListadoHtml(), self::ttyppListadoText(),
                [
                    'subject',
                    'fecha',
                    'sender_name',
                    'sender_name_html',
                    'total_registros',
                    'tabla_rows_html',
                    'tabla_rows_text',
                    'mensaje_sin_registros',
                ],
            ],
            'bitacora_pruebas.notificacion' => [
                '{{ subject }}', self::bitacoraPruebasHtml(), self::bitacoraPruebasText(),
                ['subject', 'sender_name', 'line', 'line_html', 'rit', 'rit_html', 'documentos_url', 'documentos_url_html', 'bitacora_public_url', 'bitacora_public_url_html'],
            ],
            'legacy.user_account_status' => [
                '{{ subject }}', self::legacyUserStatusHtml(), self::legacyUserStatusText(),
                ['user_name', 'user_name_html', 'status', 'subject', 'line', 'admin_name', 'admin_line', 'admin_line_text'],
            ],
            'insumo.solicitado', 'insumo.asignado', 'insumo.stock_bajo' => [
                '{{ title }}', self::insumosHtml(), self::insumosText(),
                ['title', 'line', 'line_html', 'items_html', 'items_text', 'firma_html', 'firma_text'],
            ],
            'turno1.resumen_semanal' => [
                'Turno Semanal - Semana {{ semana_rango }}', self::turno1ResumenSemanalHtml(), self::turno1ResumenSemanalText(),
                [
                    'fecha_turno',
                    'semana_rango',
                    'semana_rango_html',
                    'tribunal_oral',
                    'tribunal_oral_html',
                    'juez_despacho_run',
                    'juez_despacho_run_html',
                    'juez_despacho_nombre',
                    'juez_despacho_nombre_html',
                    'juez_despacho_telefono',
                    'juez_despacho_telefono_html',
                    'juez_despacho_correo',
                    'juez_despacho_correo_html',
                    'juez_despacho_cargo',
                    'juez_despacho_cargo_html',
                    'jefe_turno_run',
                    'jefe_turno_run_html',
                    'jefe_turno_nombre',
                    'jefe_turno_nombre_html',
                    'jefe_turno_telefono',
                    'jefe_turno_telefono_html',
                    'jefe_turno_correo',
                    'jefe_turno_correo_html',
                    'jefe_turno_observaciones',
                    'jefe_turno_observaciones_html',
                    'tribunal_rowspan',
                    'funcionarios_rows_html',
                    'funcionarios_rows_text',
                ],
            ],
            'turno2.acd.resumen_semanal' => [
                'Turno 2 ACD - Periodo {{ semana_rango }}', self::turno2AcdResumenSemanalHtml(), self::turno2AcdResumenSemanalText(),
                [
                    'fecha_turno',
                    'semana_rango',
                    'semana_rango_html',
                    'tribunal_oral',
                    'tribunal_oral_html',
                    'magistrados_rows_html',
                    'jefatura_run',
                    'jefatura_run_html',
                    'jefatura_nombre',
                    'jefatura_nombre_html',
                    'jefatura_telefono',
                    'jefatura_telefono_html',
                    'jefatura_correo',
                    'jefatura_correo_html',
                    'jefatura_observaciones',
                    'jefatura_observaciones_html',
                    'tribunal_rowspan',
                    'funcionarios_rows_html',
                    'funcionarios_rows_text',
                ],
            ],
        };

        return [
            'subject' => $subject,
            'body_html' => $html."\n".self::MARKER,
            'body_text' => $text,
            'variables_schema' => $variables,
        ];
    }

    public static function birthdayHtml(): string
    {
        return '{{ html }}';
    }

    // ---------------------------------------------------------------- TTyPP

    public static function ttyppListadoHtml(): string
    {
        $inner = 648;
        $head = self::thead([
            ['RIT', 54], ['RUC', 86], ['Tipo aud.', 70], ['Días', 34], ['Cant.', 36], ['Modal.', 66],
            ['TI MP', 40], ['TI Def.', 46], ['Zoom', 40], ['Acta', 38], ['Sala', 42], ['Jueces', 96],
        ], 'left', 10, 6);

        $body = self::row(self::facts([
            ['FECHA', '{{ fecha }}'], ['TOTAL DE REGISTROS', '{{ total_registros }}'], ['ENVIADO POR', '{{ sender_name_html }}'],
        ], $inner, 'blue'), '0 16px 16px 16px')
            .self::row(self::dataTable($inner, $head.'{{ tabla_rows_html }}'), '0 16px 8px 16px')
            .self::row('{{ mensaje_sin_registros }}', '0 16px 16px 16px', 'color:#4A5568;');

        return self::page('Listado TTyPP', 'Listado TTyPP del {{ fecha }}: {{ total_registros }} registro(s).', 'Audiencias', 'Listado TTyPP del día {{ fecha }}', $body, ['width' => 680, 'pad' => 16, 'size' => 11]);
    }

    public static function ttyppListadoText(): string
    {
        return self::text('Listado TTyPP del día {{ fecha }}', <<<'TXT'
Fecha: {{ fecha }}
Total de registros: {{ total_registros }}
Enviado por: {{ sender_name }}

Detalle (RIT | RUC | Tipo audiencia | N.º días | Cantidad | Modalidad | TI MP | TI Defensa | Zoom | Acta | Sala | Jueces):
{{ tabla_rows_text }}

{{ mensaje_sin_registros }}
TXT);
    }

    // --------------------------------------------------------------- Turno 1

    public static function turno1ResumenSemanalHtml(): string
    {
        $inner = 888;
        $c = 'center';
        $cell = fn (string $v, string $bg, string $style): string => '<th scope="col" align="center" bgcolor="'.$bg.'" style="background-color:'.$bg.';border:1px solid #CBD5E1;padding:8px 6px;font-family:'.self::FONT.';mso-line-height-rule:exactly;'.$style.'">'.$v.'</th>';
        $sub = fn (array $labels, string $bg, string $fg): string => '<tr>'.implode('', array_map(
            fn (string $l): string => $cell($l, $bg, 'font-weight:bold;color:'.$fg.';'),
            $labels,
        )).'</tr>';

        $table = self::thead([['Tribunal Oral', 120], ['RUN', 110], ['Juez del Turno', 190], ['Teléfono', 120], ['Correo', 220], ['Cargo Turno', 128]], $c, 12, 9)
            .self::drow(['{{ juez_despacho_run_html }}', '{{ juez_despacho_nombre_html }}', '{{ juez_despacho_telefono_html }}', '{{ juez_despacho_correo_html }}', '{{ juez_despacho_cargo_html }}'], self::dataCell('{{ tribunal_oral_html }}', '#EAF0F9', ' rowspan="{{ tribunal_rowspan }}" valign="middle"'))
            .$sub(['RUN', 'Jefe de Turno', 'Teléfono', 'Correo', 'Observaciones'], '#DCE6F5', self::NAVY)
            .self::drow(['{{ jefe_turno_run_html }}', '{{ jefe_turno_nombre_html }}', '{{ jefe_turno_telefono_html }}', '{{ jefe_turno_correo_html }}', '{{ jefe_turno_observaciones_html }}'])
            .$sub(['RUN', 'Función de equipo 1', 'Teléfono', 'Correo', 'Función Equipo'], '#FFF4DC', '#6B4200')
            .'{{ funcionarios_rows_html }}';

        $body = self::row(self::facts([['SEMANA', '{{ semana_rango_html }}', 560], ['TRIBUNAL', '{{ tribunal_oral_html }}', 328]], $inner, 'blue'), '0 16px 14px 16px')
            .self::row('Buenos días, se adjunta turno de la semana.', '0 16px 14px 16px')
            .self::row(self::dataTable($inner, $table), '0 16px 20px 16px');

        return self::page('Turno semanal', 'Turno de despacho, semana {{ semana_rango_html }}.', 'Turnos', 'Turno semanal de despacho', $body, ['width' => 920, 'pad' => 16, 'size' => 12]);
    }

    public static function turno1ResumenSemanalText(): string
    {
        return self::text('Turno semanal de despacho', <<<'TXT'
Semana: {{ semana_rango }}
Tribunal: {{ tribunal_oral }}

JUEZ DE DESPACHO
{{ juez_despacho_nombre }} (RUN {{ juez_despacho_run }})
Teléfono: {{ juez_despacho_telefono }} | Correo: {{ juez_despacho_correo }} | {{ juez_despacho_cargo }}

JEFE DE TURNO
{{ jefe_turno_nombre }} (RUN {{ jefe_turno_run }})
Teléfono: {{ jefe_turno_telefono }} | Correo: {{ jefe_turno_correo }} | {{ jefe_turno_observaciones }}

FUNCIONARIOS DE EQUIPO (RUN | Nombre | Teléfono | Correo | Función)
{{ funcionarios_rows_text }}
TXT);
    }

    // ------------------------------------------------------------ Turno 2 ACD

    public static function turno2AcdResumenSemanalHtml(): string
    {
        $inner = 888;
        $sub = fn (array $labels, string $bg, string $fg): string => '<tr>'.implode('', array_map(
            fn (string $l): string => '<th scope="col" align="center" bgcolor="'.$bg.'" style="background-color:'.$bg.';border:1px solid #CBD5E1;padding:7px 6px;font-family:'.self::FONT.';font-size:11px;line-height:14px;font-weight:bold;color:'.$fg.';mso-line-height-rule:exactly;">'.$l.'</th>',
            $labels,
        )).'</tr>';

        $table = self::thead([['Tribunal', 90], ['RUN', 120], ['Magistrados ACD', 190], ['Teléfono', 130], ['Correo', 238], ['Cargo turno', 120]], 'center', 11, 8)
            .'{{ magistrados_rows_html }}'
            .$sub(['RUN', 'Jefatura', 'Teléfono', 'Correo', 'Observaciones'], '#DCE6F5', self::NAVY)
            .self::drow(['{{ jefatura_run_html }}', '{{ jefatura_nombre_html }}', '{{ jefatura_telefono_html }}', '{{ jefatura_correo_html }}', '{{ jefatura_observaciones_html }}'])
            .$sub(['RUN', 'Funcionario(a) de equipo', 'Teléfono', 'Correo', 'Función equipo'], '#FFF4DC', '#6B4200')
            .'{{ funcionarios_rows_html }}';

        $body = self::row(self::facts([['PERIODO', '{{ semana_rango_html }}', 560], ['TRIBUNAL', '{{ tribunal_oral_html }}', 328]], $inner, 'blue'), '0 16px 14px 16px')
            .self::row('Se informa el turno 2 ACD del período.', '0 16px 14px 16px')
            .self::row(self::dataTable($inner, $table), '0 16px 20px 16px');

        return self::page('Turno 2 ACD', 'Turno 2 ACD, período {{ semana_rango_html }}.', 'Turnos', 'Turno 2 ACD', $body, ['width' => 920, 'pad' => 16, 'size' => 12]);
    }

    public static function turno2AcdResumenSemanalText(): string
    {
        return self::text('Turno 2 ACD', <<<'TXT'
Período: {{ semana_rango }}
Tribunal: {{ tribunal_oral }}

MAGISTRADOS ACD
{{ magistrados.0.nombre }} (RUN {{ magistrados.0.run }}) - {{ magistrados.0.cargo }}
{{ magistrados.1.nombre }} (RUN {{ magistrados.1.run }}) - {{ magistrados.1.cargo }}
{{ magistrados.2.nombre }} (RUN {{ magistrados.2.run }}) - {{ magistrados.2.cargo }}

JEFATURA
{{ jefatura_nombre }} (RUN {{ jefatura_run }})
Teléfono: {{ jefatura_telefono }} | Correo: {{ jefatura_correo }} | {{ jefatura_observaciones }}

FUNCIONARIOS DE EQUIPO (RUN | Nombre | Teléfono | Correo | Función)
{{ funcionarios_rows_text }}
TXT);
    }

    // ------------------------------------------------------------- Ausentismo

    public static function ausentismoResumenHtml(): string
    {
        $body = self::row(self::facts([['FECHA', '{{ fecha_reporte }}'], ['TOTAL DE AUSENTES', '{{ total_ausentes }}']], 592, 'blue'))
            .self::row('Funcionarios no disponibles según el registro del día.', '0 24px 12px 24px')
            .self::row('{{ ausentes_html }}', '0 24px 8px 24px')
            .self::row('{{ mensaje_sin_novedades }}', '0 24px 18px 24px', 'color:#4A5568;');

        return self::page('Resumen diario de ausentismo', 'Ausentismo del {{ fecha_reporte }}: {{ total_ausentes }} funcionario(s).', 'Ausentismo', 'Resumen diario de ausentismo', $body, ['size' => 13]);
    }

    public static function ausentismoResumenText(): string
    {
        return self::text('Resumen diario de ausentismo', <<<'TXT'
Fecha: {{ fecha_reporte }}
Total de ausentes: {{ total_ausentes }}

{{ ausentes_text }}

{{ mensaje_sin_novedades }}
TXT);
    }

    // ----------------------------------------------------- Estado de cuenta

    public static function legacyUserStatusHtml(): string
    {
        $body = self::row('Hola {{ user_name_html }},', '0 24px 10px 24px', 'font-size:15px;')
            .self::row('{{ line }}', '0 24px 10px 24px')
            .self::row('{{ admin_line }}', '0 24px 10px 24px', 'color:#4A5568;')
            .self::row('Si tienes dudas, contacta al administrador del sistema.', '0 24px 22px 24px', 'color:#4A5568;');

        return self::page('{{ subject }}', '{{ line }}', 'Estado de cuenta', '{{ subject }}', $body, ['size' => 14]);
    }

    public static function legacyUserStatusText(): string
    {
        return self::text('{{ subject }}', <<<'TXT'
Hola {{ user_name }},

{{ line }}
{{ admin_line_text }}

Si tienes dudas, contacta al administrador del sistema.
TXT);
    }

    // ------------------------------------------------------------ PP / cárcel

    public static function visitaCarcelRevisionPpHtml(): string
    {
        $head = self::thead([['Nombre imputado', 160], ['RUT', 82], ['RIT', 66], ['RUC', 96], ['Última revisión', 96], ['Día de plazo', 92]], 'left', 11, 6, '#6B4200');
        $inner = 592;

        $body = self::row(self::facts([['FECHA', '{{ fecha }}'], ['IMPUTADOS VIGENTES', '{{ total_imputados }}']], $inner, 'amber'))
            .self::row('Imputados que se encuentran entre los días 160 y 180 desde su última revisión de prisión preventiva.', '0 24px 12px 24px')
            .self::row(self::dataTable($inner, $head.'{{ tabla_rows_html }}'), '0 24px 8px 24px')
            .self::row('{{ mensaje_sin_imputados }}', '0 24px 18px 24px', 'color:#4A5568;');

        return self::page('Revisión de prisión preventiva', 'Revisión de PP del {{ fecha }}: {{ total_imputados }} imputado(s) vigente(s).', 'Alerta · Prisión preventiva', 'Revisión de prisión preventiva', $body, ['accent' => 'amber', 'size' => 12]);
    }

    public static function visitaCarcelRevisionPpText(): string
    {
        return self::text('Revisión de prisión preventiva', <<<'TXT'
Fecha: {{ fecha }}
Imputados vigentes: {{ total_imputados }}

Imputados entre los días 160 y 180 desde su última revisión de PP
(Nombre | RUT | RIT | RUC | Última revisión | Día de plazo):
{{ tabla_rows_text }}

{{ mensaje_sin_imputados }}
TXT);
    }

    // --------------------------------------------------------------- Bitácora

    public static function bitacoraPruebasHtml(): string
    {
        $note = '<table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr>'
            .'<td bgcolor="#FFF4DC" style="background-color:#FFF4DC;border-left:4px solid #C77F0A;padding:10px 14px;font-family:'.self::FONT.';font-size:12px;line-height:18px;color:#5C3A00;mso-line-height-rule:exactly;">'
            .'<strong>Importante:</strong> para visualizar la bitácora de pruebas es necesario estar conectado a la VPN corporativa.</td></tr></table>';

        $body = self::row('{{ line_html }}', '0 24px 14px 24px')
            .self::row(self::facts([['CAUSA RIT', '{{ rit_html }}']], 592, 'blue'))
            .self::row(self::buttons([
                ['{{ documentos_url_html }}', 'Ir al enlace de documentos', '#0F766E'],
                ['{{ bitacora_public_url_html }}', 'Ir a la bitácora de pruebas', self::NAVY],
            ], 280), '0 24px 8px 24px')
            .self::row($note, '0 24px 22px 24px');

        return self::page('{{ subject }}', '{{ line_html }} Causa RIT {{ rit_html }}.', 'Bitácora de pruebas', 'Notificación de bitácora de pruebas', $body, ['size' => 14]);
    }

    public static function bitacoraPruebasText(): string
    {
        return self::text('Notificación de bitácora de pruebas', <<<'TXT'
{{ line }}

Causa RIT: {{ rit }}

Enlace de documentos: {{ documentos_url }}
Bitácora de pruebas: {{ bitacora_public_url }}

Importante: para visualizar la bitácora de pruebas es necesario estar conectado a la VPN corporativa.
TXT);
    }

    // ----------------------------------------------------------------- Insumos

    public static function insumosHtml(): string
    {
        $list = '<table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr>'
            .'<td bgcolor="#EAF0F9" style="background-color:#EAF0F9;border-left:4px solid #2F5597;padding:12px 14px 12px 14px;font-family:'.self::FONT.';font-size:14px;line-height:22px;color:#1F2937;mso-line-height-rule:exactly;">'
            .'<span style="font-size:11px;line-height:14px;font-weight:bold;color:#4A5568;">DETALLE</span>'
            .'<ul style="margin:6px 0 0 0;padding-left:20px;font-family:'.self::FONT.';font-size:14px;line-height:22px;">{{ items_html }}</ul></td></tr></table>';

        $body = self::row('{{ line_html }}', '0 24px 14px 24px', 'font-size:15px;line-height:22px;')
            .self::row($list, '0 24px 12px 24px')
            .self::row('{{ firma_html }}', '0 24px 22px 24px');

        return self::page('{{ title }}', '{{ line_html }}', 'Insumos', '{{ title }}', $body, ['size' => 14]);
    }

    public static function insumosText(): string
    {
        return self::text('{{ title }}', <<<'TXT'
{{ line }}

Detalle: {{ items_text }}

{{ firma_text }}
TXT);
    }

    // ================================================================ helpers

    private static function text(string $title, string $body): string
    {
        return "4° Tribunal de Juicio Oral en lo Penal de Santiago\nSistema de Notificaciones 4°TOP\n\n".$title."\n\n".$body."\n\n--\nCorreo automático del sistema 4°TOP – no responder.";
    }

    /**
     * @param  array{width?: int, pad?: int, size?: int, accent?: string}  $o
     */
    private static function page(string $title, string $pre, string $kicker, string $heading, string $body, array $o = []): string
    {
        $w = $o['width'] ?? 640;
        $pad = $o['pad'] ?? 24;
        $size = $o['size'] ?? 13;
        $acc = self::ACCENTS[$o['accent'] ?? 'blue'];
        $f = self::FONT;
        $navy = self::NAVY;
        $lh = $size + 6;
        $kick = mb_strtoupper($kicker, 'UTF-8');

        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<title>{$title}</title>
<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<style type="text/css">
body{margin:0;padding:0;}
table{border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;}
td,th,p,li{font-family:{$f};font-size:{$size}px;line-height:{$lh}px;color:#1F2937;}
a{color:#1F5FA8;}
</style>
</head>
<body bgcolor="#EEF1F6" style="margin:0;padding:0;background-color:#EEF1F6;">
<div style="display:none;mso-hide:all;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;color:#EEF1F6;">{$pre}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#EEF1F6" style="width:100%;background-color:#EEF1F6;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
<tr><td align="center" style="padding:20px 8px;">
<table role="presentation" width="{$w}" align="center" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="width:{$w}px;background-color:#FFFFFF;border:1px solid #D5DCE8;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
<tr><td bgcolor="{$navy}" style="background-color:{$navy};padding:16px {$pad}px 14px {$pad}px;font-family:{$f};">
<span style="font-size:11px;line-height:15px;letter-spacing:1px;color:#C9D6EE;mso-line-height-rule:exactly;">PODER JUDICIAL · REPÚBLICA DE CHILE</span><br>
<span style="font-size:17px;line-height:24px;font-weight:bold;color:#FFFFFF;mso-line-height-rule:exactly;">4° Tribunal de Juicio Oral en lo Penal de Santiago</span><br>
<span style="font-size:12px;line-height:18px;color:#C9D6EE;mso-line-height-rule:exactly;">Sistema de Notificaciones 4°TOP</span>
</td></tr>
<tr><td height="4" bgcolor="{$acc['bar']}" style="height:4px;background-color:{$acc['bar']};font-size:1px;line-height:4px;mso-line-height-rule:exactly;">&nbsp;</td></tr>
<tr><td style="padding:20px {$pad}px 14px {$pad}px;font-family:{$f};">
<span style="font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1px;color:{$acc['text']};mso-line-height-rule:exactly;">{$kick}</span><br>
<h1 style="margin:0;padding:0;font-family:{$f};font-size:22px;line-height:28px;font-weight:bold;color:{$navy};mso-line-height-rule:exactly;">{$heading}</h1>
</td></tr>
{$body}
<tr><td bgcolor="#F3F5F9" style="background-color:#F3F5F9;border-top:1px solid #D5DCE8;padding:14px {$pad}px;font-family:{$f};font-size:12px;line-height:18px;color:#4A5568;mso-line-height-rule:exactly;">
Correo automático del sistema 4°TOP – no responder.<br>
4° Tribunal de Juicio Oral en lo Penal de Santiago
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private static function row(string $html, string $pad = '0 24px 18px 24px', string $style = ''): string
    {
        return '<tr><td align="left" style="padding:'.$pad.';font-family:'.self::FONT.';line-height:21px;color:#1F2937;mso-line-height-rule:exactly;'.$style.'">'.$html.'</td></tr>';
    }

    private static function label(string $text): string
    {
        return '<span style="font-size:12px;line-height:16px;font-weight:bold;letter-spacing:1px;color:'.self::NAVY.';">'.mb_strtoupper($text, 'UTF-8').'</span>';
    }

    /**
     * Bloque de datos clave. Cada ítem: [etiqueta, valor, ancho opcional].
     *
     * @param  list<array{0: string, 1: string, 2?: int}>  $items
     */
    private static function facts(array $items, int $inner, string $accent): string
    {
        $acc = self::ACCENTS[$accent];
        $equal = intdiv($inner, count($items));
        $cells = '';
        foreach ($items as $i => $item) {
            $cw = $item[2] ?? $equal;
            $border = $i === 0 ? 'border-left:4px solid '.$acc['bar'].';' : 'border-left:1px solid #FFFFFF;';
            $cells .= '<td width="'.$cw.'" valign="top" bgcolor="'.$acc['soft'].'" style="width:'.$cw.'px;background-color:'.$acc['soft'].';'.$border.'padding:10px 14px;font-family:'.self::FONT.';mso-line-height-rule:exactly;">'
                .'<span style="font-size:11px;line-height:15px;font-weight:bold;color:#4A5568;">'.$item[0].'</span><br>'
                .'<span style="font-size:16px;line-height:22px;font-weight:bold;color:'.self::NAVY.';">'.$item[1].'</span></td>';
        }

        return '<table role="presentation" width="'.$inner.'" border="0" cellpadding="0" cellspacing="0" style="width:'.$inner.'px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr>'.$cells.'</tr></table>';
    }

    /** Tabla de datos (sin role="presentation" para conservar la semántica de encabezados). */
    private static function dataTable(int $width, string $rows): string
    {
        return '<table width="'.$width.'" border="0" cellpadding="0" cellspacing="0" style="width:'.$width.'px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">'.$rows.'</table>';
    }

    /**
     * @param  list<array{0: string, 1: int}>  $cols  [etiqueta, ancho]
     */
    private static function thead(array $cols, string $align, int $size, int $pad, string $bg = self::NAVY): string
    {
        $cells = '';
        foreach ($cols as [$label, $w]) {
            $cells .= '<th width="'.$w.'" align="'.$align.'" bgcolor="'.$bg.'" style="width:'.$w.'px;background-color:'.$bg.';border:1px solid '.$bg.';padding:'.$pad.'px 6px;font-family:'.self::FONT.';font-size:'.$size.'px;line-height:'.($size + 4).'px;font-weight:bold;color:#FFFFFF;text-align:'.$align.';mso-line-height-rule:exactly;">'.$label.'</th>';
        }

        return '<tr>'.$cells.'</tr>';
    }

    public static function funcionariosRows(array $funcionarios): string
    {
        if ($funcionarios === []) {
            return '<tr>'.self::dataCell('Sin funcionarios asignados.', attributes: ' colspan="5"').'</tr>';
        }

        return implode('', array_map(fn (array $person): string => self::personRow($person), $funcionarios));
    }

    public static function personRow(array $person, ?string $tribunal = null, int $rowspan = 0): string
    {
        $correo = e((string) ($person['correo'] ?? '-'));
        $correoHtml = $correo !== '-' ? '<a href="mailto:'.$correo.'" style="'.self::DATA_TYPOGRAPHY.'">'.$correo.'</a>' : '-';
        $tribunalCell = $tribunal === null
            ? ''
            : self::dataCell(e($tribunal), '#EAF0F9', ' rowspan="'.e((string) $rowspan).'" valign="middle"');

        return self::drow([
            e((string) ($person['run'] ?? '-')),
            e((string) ($person['nombre'] ?? '-')),
            e((string) ($person['telefono'] ?? '-')),
            $correoHtml,
            e((string) ($person['cargo'] ?? '-')),
        ], $tribunalCell);
    }

    /** @param  list<string>  $values */
    private static function drow(array $values, string $firstCell = ''): string
    {
        return '<tr>'.$firstCell.implode('', array_map(fn (string $value): string => self::dataCell($value), $values)).'</tr>';
    }

    private static function dataCell(string $value, string $bg = '#FFFFFF', string $attributes = ''): string
    {
        return '<td'.$attributes.' align="center" bgcolor="'.$bg.'" style="background-color:'.$bg.';border:1px solid #CBD5E1;padding:8px 6px;'.self::DATA_TYPOGRAPHY.'">'.$value.'</td>';
    }

    /** Fila espaciadora explícita (Word ignora márgenes entre bloques). */
    private static function spacer(int $h = 12): string
    {
        return '<tr><td height="'.$h.'" style="height:'.$h.'px;font-size:'.$h.'px;line-height:'.$h.'px;mso-line-height-rule:exactly;">&nbsp;</td></tr>';
    }

    /** Botón sin VML: celda con fondo sólido (Word la pinta de forma fiable). Devuelve una fila. */
    private static function buttonRow(string $href, string $label, string $fill): string
    {
        $f = self::FONT;

        return '<tr><td align="center" bgcolor="'.$fill.'" style="background-color:'.$fill.';padding:12px 20px;mso-padding-alt:12px 20px;">'
            .'<a href="'.$href.'" target="_blank" rel="noopener noreferrer" style="display:inline-block;color:#FFFFFF;font-family:'.$f.';font-size:14px;line-height:18px;font-weight:bold;text-decoration:none;mso-line-height-rule:exactly;">'.$label.'</a>'
            .'</td></tr>';
    }

    /**
     * Uno o varios botones apilados, con espacio antes, entre y después.
     *
     * @param  list<array{0: string, 1: string, 2: string}>  $buttons  [href, etiqueta, color]
     */
    private static function buttons(array $buttons, int $w): string
    {
        $rows = self::spacer(8);
        foreach ($buttons as $i => [$href, $label, $fill]) {
            $rows .= ($i > 0 ? self::spacer(12) : '').self::buttonRow($href, $label, $fill);
        }
        $rows .= self::spacer(12);

        return '<table role="presentation" width="'.$w.'" border="0" cellpadding="0" cellspacing="0" style="width:'.$w.'px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">'.$rows.'</table>';
    }
}
