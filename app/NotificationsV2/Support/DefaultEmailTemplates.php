<?php

namespace App\NotificationsV2\Support;

class DefaultEmailTemplates
{
    public static function turnoAsignadoHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Asignacion de Turno</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="920" style="width:920px;max-width:920px;background:#ffffff;border:1px solid #d1d5db;">
          <tr>
            <td style="padding:14px 16px 8px 16px;font-family:Arial,Helvetica,sans-serif;color:#1e3a8a;font-size:26px;line-height:30px;font-weight:bold;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago
            </td>
          </tr>
          <tr>
            <td style="padding:0 16px 12px 16px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;font-size:14px;line-height:22px;">
              Buenos dias, se adjunta turno de la semana.
            </td>
          </tr>
          <tr>
            <td style="padding:0 16px 16px 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#ecefe2;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:14px;line-height:20px;">
                <tr>
                  <td colspan="6" style="border:1px solid #111827;padding:8px 10px;text-align:center;font-weight:bold;font-size:30px;line-height:34px;">
                    Turno Semanal<br>
                    <span style="font-size:28px;line-height:30px;">Semana del {{ fecha_turno }}</span>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">Tribunal Oral</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">RUN</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">Juez del Turno</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">TELEFONO</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">CORREO</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">Cargo Turno</td>
                </tr>
                <tr>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;font-weight:bold;">4to TOP</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;">-</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;">{{ nombre_asignado }}</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;">-</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;">-</td>
                  <td style="border:1px solid #111827;padding:8px;text-align:center;">{{ bloque_turno }} / {{ slot_turno }}</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function turnoAsignadoText(): string
    {
        return 'Se ha registrado una asignacion de turno. Fecha: {{ fecha_turno }}. Bloque: {{ bloque_turno }}. Slot: {{ slot_turno }}. Asignado a: {{ nombre_asignado }}.';
    }

    public static function ttyppListadoHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Listado TTyPP</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="1120" style="width:1120px;max-width:1120px;background:#ffffff;border:1px solid #cfd8e3;">
          <tr>
            <td style="background:#0d3b66;padding:16px 18px 10px 18px;font-family:Arial,Helvetica,sans-serif;color:#ffffff;font-size:24px;line-height:28px;font-weight:bold;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago
            </td>
          </tr>
          <tr>
            <td style="background:#0d3b66;padding:0 18px 14px 18px;font-family:Arial,Helvetica,sans-serif;color:#dbeafe;font-size:14px;line-height:20px;">
              Listado TTyPP correspondiente al dia {{ fecha }}. Enviado por {{ sender_name }}.
            </td>
          </tr>
          <tr>
            <td style="padding:14px 16px 16px 16px;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:13px;line-height:18px;">
              <p style="margin:0 0 10px 0;"><strong>Total registros:</strong> {{ total_registros }}</p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:12px;line-height:16px;border:1px solid #cbd5e1;">
                <tr>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">RIT</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">RUC</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Tipo AUD</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">N Dias</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Cant.</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Modalidad</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">TI MP</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">TI Defensa</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Zoom</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Acta</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Sala</th>
                  <th style="border:1px solid #cbd5e1;padding:6px;background:#e2e8f0;text-align:left;">Jueces</th>
                </tr>
                {{ tabla_rows_html }}
              </table>
              {{ mensaje_sin_registros }}
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function ttyppListadoText(): string
    {
        return 'Listado TTyPP del dia {{ fecha }}. Total registros: {{ total_registros }}. {{ tabla_rows_text }} {{ mensaje_sin_registros }}';
    }

    public static function turno1ResumenSemanalHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Turno Semanal</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="920" style="width:920px;max-width:920px;background:#ffffff;border:1px solid #cfd8e3;">
          <tr>
            <td style="background:#0d3b66;padding:16px 18px 10px 18px;font-family:Arial,Helvetica,sans-serif;color:#ffffff;font-size:24px;line-height:28px;font-weight:bold;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago
            </td>
          </tr>
          <tr>
            <td style="background:#0d3b66;padding:0 18px 14px 18px;font-family:Arial,Helvetica,sans-serif;color:#dbeafe;font-size:14px;line-height:20px;">
              Buenos dias, se adjunta turno de la semana.
            </td>
          </tr>
          <tr>
            <td style="padding:14px 16px 16px 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:14px;line-height:20px;border:1px solid #cbd5e1;">
                <tr>
                  <td colspan="6" style="border:1px solid #cbd5e1;padding:12px 10px;text-align:center;font-weight:bold;font-size:28px;line-height:30px;background:#e2e8f0;color:#0f172a;">
                    Turno Semanal<br>
                    <span style="font-size:22px;line-height:26px;color:#1e3a8a;">Semana del {{ semana_rango }}</span>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Tribunal Oral</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Juez del Turno</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Cargo Turno</td>
                </tr>
                <tr>
                  <td rowspan="{{ tribunal_rowspan }}" style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#f1f5f9;font-size:18px;line-height:22px;">{{ tribunal_oral }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ juez_despacho_run }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ juez_despacho_nombre }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ juez_despacho_telefono }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ juez_despacho_correo }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ juez_despacho_cargo }}</td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Jefe de Turno</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Observaciones</td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefe_turno_run }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ jefe_turno_nombre }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefe_turno_telefono }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefe_turno_correo }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefe_turno_observaciones }}</td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;font-size:16px;line-height:20px;background:#fef3c7;color:#92400e;">Funcion de equipo 1</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">Funcion Equipo</td>
                </tr>
                {{ funcionarios_rows_html }}
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function turno1ResumenSemanalText(): string
    {
        return 'Turno de Despacho semana {{ semana_rango }}. Juez: {{ juez_despacho_nombre }} ({{ juez_despacho_run }}). Jefe de Turno: {{ jefe_turno_nombre }} ({{ jefe_turno_run }}). Funcionarios: {{ funcionarios_rows_text }}.';
    }

    public static function turno2AcdResumenSemanalHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Turno 2 ACD</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="920" style="width:920px;max-width:920px;background:#ffffff;border:1px solid #cfd8e3;">
          <tr>
            <td style="background:#0d3b66;padding:16px 18px 10px 18px;font-family:Arial,Helvetica,sans-serif;color:#ffffff;font-size:24px;line-height:28px;font-weight:bold;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago
            </td>
          </tr>
          <tr>
            <td style="background:#0d3b66;padding:0 18px 14px 18px;font-family:Arial,Helvetica,sans-serif;color:#dbeafe;font-size:14px;line-height:20px;">
              Buenos dias, se adjunta turno 2 ACD del periodo.
            </td>
          </tr>
          <tr>
            <td style="padding:14px 16px 16px 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:14px;line-height:20px;border:1px solid #cbd5e1;">
                <tr>
                  <td colspan="6" style="border:1px solid #cbd5e1;padding:12px 10px;text-align:center;font-weight:bold;font-size:28px;line-height:30px;background:#e2e8f0;color:#0f172a;">
                    Turno 2 ACD<br>
                    <span style="font-size:22px;line-height:26px;color:#1e3a8a;">Periodo {{ semana_rango }}</span>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Tribunal Oral</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Magistrados ACD</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:9px;text-align:center;font-weight:bold;background:#0d3b66;color:#ffffff;">Cargo Turno</td>
                </tr>
                {{ magistrados_rows_html }}
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Jefatura</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Observaciones</td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefatura_run }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ jefatura_nombre }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefatura_telefono }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefatura_correo }}</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ jefatura_observaciones }}</td>
                </tr>
                <tr>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">RUN</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;font-size:16px;line-height:20px;background:#fef3c7;color:#92400e;">Funcionarios</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">TELEFONO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">CORREO</td>
                  <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">Funcion Equipo</td>
                </tr>
                {{ funcionarios_rows_html }}
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function turno2AcdResumenSemanalText(): string
    {
        return 'Turno 2 ACD periodo {{ semana_rango }}. Jefatura: {{ jefatura_nombre }} ({{ jefatura_run }}). Funcionarios: {{ funcionarios_rows_text }}.';
    }

    public static function ausentismoResumenHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Resumen Diario de Ausentismo</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width:680px;max-width:680px;background:#ffffff;border:1px solid #dfe3e8;">
          <tr>
            <td style="background:#7c2d12;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:26px;font-weight:bold;padding:16px 20px;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago<br>
              <span style="font-size:14px;line-height:20px;font-weight:normal;">Resumen Diario de Ausentismo</span>
            </td>
          </tr>
          <tr>
            <td style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;font-size:14px;line-height:22px;">
              <p style="margin:0 0 8px 0;"><strong>Fecha:</strong> {{ fecha_reporte }}</p>
              <p style="margin:0 0 14px 0;"><strong>Total ausentes:</strong> {{ total_ausentes }}</p>
              {{ ausentes_html }}
              <p style="margin:14px 0 0 0;">{{ mensaje_sin_novedades }}</p>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function ausentismoResumenText(): string
    {
        return 'Resumen diario de ausentismo. Fecha: {{ fecha_reporte }}. Total ausentes: {{ total_ausentes }}. {{ ausentes_text }} {{ mensaje_sin_novedades }}';
    }

    public static function legacyUserStatusHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Estado de Cuenta</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="width:640px;max-width:640px;background:#ffffff;border:1px solid #dfe3e8;">
          <tr>
            <td style="background:#14532d;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:26px;font-weight:bold;padding:16px 20px;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago<br>
              <span style="font-size:14px;line-height:20px;font-weight:normal;">Estado de Cuenta</span>
            </td>
          </tr>
          <tr>
            <td style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;font-size:14px;line-height:22px;">
              <p style="margin:0 0 10px 0;">Hola {{ user_name }},</p>
              <p style="margin:0 0 10px 0;">{{ line }}</p>
              {{ admin_line }}
              <p style="margin:10px 0 0 0;">Si tienes dudas, contacta al administrador del sistema.</p>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function visitaCarcelRevisionPpHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es"><body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td align="center"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="960" style="width:960px;max-width:960px;background:#ffffff;border:1px solid #cbd5e1;">
<tr><td style="background:#92400e;padding:16px 18px;color:#ffffff;font-size:22px;font-weight:bold;">4to Tribunal de Juicio Oral en lo Penal de Santiago<br><span style="font-size:14px;font-weight:normal;">Revisión de prisión preventiva</span></td></tr>
<tr><td style="padding:18px;font-size:14px;line-height:20px;"><p style="margin:0 0 10px;"><strong>Fecha:</strong> {{ fecha }} &nbsp; <strong>Imputados vigentes:</strong> {{ total_imputados }}</p><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-size:12px;"><tr><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">Nombre imputado</th><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">RUT</th><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">RIT</th><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">RUC</th><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">Última revisión PP</th><th style="border:1px solid #cbd5e1;padding:6px;background:#fef3c7;text-align:left;">Día plazo</th></tr>{{ tabla_rows_html }}</table><p style="margin:14px 0 0;">{{ mensaje_sin_imputados }}</p></td></tr>
<tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 18px;color:#4b5563;font-size:12px;">Correo automático del Sistema de Notificaciones.</td></tr>
</table></td></tr></table></body></html>
HTML;
    }

    public static function visitaCarcelRevisionPpText(): string
    {
        return 'Revisión de prisión preventiva. Fecha: {{ fecha }}. Imputados vigentes: {{ total_imputados }}. {{ tabla_rows_text }} {{ mensaje_sin_imputados }}';
    }

    public static function legacyUserStatusText(): string
    {
        return 'Hola {{ user_name }}. {{ line }} {{ admin_line_text }} Si tienes dudas, contacta al administrador del sistema.';
    }

    public static function bitacoraPruebasHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>{{ subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f5f7;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="width:640px;max-width:640px;background:#ffffff;border:1px solid #dfe3e8;">
          <tr>
            <td style="background:#14532d;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:26px;font-weight:bold;padding:16px 20px;">
              4to Tribunal de Juicio Oral en lo Penal de Santiago<br>
              <span style="font-size:14px;line-height:20px;font-weight:normal;">Notificacion de Bitacora de Pruebas</span>
            </td>
          </tr>
          <tr>
            <td style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;font-size:14px;line-height:22px;">
              <p style="margin:0 0 16px 0;">{{ line }}</p>

              <p style="margin:0 0 16px 0;"><strong>Causa RIT :</strong> {{ rit }}</p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 12px auto;">
                <tr>
                  <td style="padding:0 6px 0 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="border-radius:6px;background:#0f766e;text-align:center;white-space:nowrap;">
                          <a href="{{ documentos_url }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">Ir a enlace documentos</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td style="padding:0 0 0 6px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="border-radius:6px;background:#1d4ed8;text-align:center;white-space:nowrap;">
                          <a href="{{ bitacora_public_url }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">Ir a bitacora de pruebas</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:16px 0 0 0;color:#4b5563;font-size:12px;line-height:18px;">Para visualizar la bitacora de pruebas deben estar conectados a la VPN corporativa.</p>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:14px 20px;font-family:Arial,Helvetica,sans-serif;color:#4b5563;font-size:12px;line-height:18px;">
              Correo automatico del Sistema de Notificaciones.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function bitacoraPruebasText(): string
    {
        return '{{ line }} RIT: {{ rit }} Documentos: {{ documentos_url }} Bitacora publica: {{ bitacora_public_url }} Nota: Para visualizar la bitacora de pruebas deben estar conectados a la VPN corporativa.';
    }
}
