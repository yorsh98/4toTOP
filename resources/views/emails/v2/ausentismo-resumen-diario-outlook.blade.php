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
                        <p style="margin:0 0 8px 0;"><strong>Fecha:</strong> {{ $fechaReporte }}</p>
                        <p style="margin:0 0 14px 0;"><strong>Total ausentes:</strong> {{ count($ausentes) }}</p>

                        @if(count($ausentes) > 0)
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">
                                <tr>
                                    <th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Funcionario</th>
                                    <th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Cargo</th>
                                    <th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Tipo permiso</th>
                                    <th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Rango</th>
                                    <th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Observacion</th>
                                </tr>
                                @foreach($ausentes as $ausente)
                                    <tr>
                                        <td style="border:1px solid #e5e7eb;padding:8px;">{{ $ausente['funcionario_nombre'] }}</td>
                                        <td style="border:1px solid #e5e7eb;padding:8px;">{{ $ausente['cargo'] }}</td>
                                        <td style="border:1px solid #e5e7eb;padding:8px;">{{ $ausente['tipo_permiso'] }}</td>
                                        <td style="border:1px solid #e5e7eb;padding:8px;">{{ $ausente['fecha_inicio'] }} a {{ $ausente['fecha_termino'] }}</td>
                                        <td style="border:1px solid #e5e7eb;padding:8px;">{{ $ausente['observacion'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p style="margin:14px 0 0 0;">no hay ausencias registradas</p>
                        @endif
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
