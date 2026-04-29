<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $subject ?? 'Notificacion de Bitacora de Pruebas' }}</title>
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
                        <p style="margin:0 0 10px 0;">Hola {{ $recipientName ?? 'Usuario' }},</p>
                        <p style="margin:0 0 16px 0;">{{ $senderName }} le ha enviado esta notificacion.</p>
                        @if(!empty($rit))
                            <p style="margin:0 0 16px 0;"><strong>Causa RIT :</strong> {{ $rit }}</p>
                        @endif

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 12px auto;">
                            <tr>
                                <td style="padding:0 6px 0 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="border-radius:6px;background:#0f766e;text-align:center;white-space:nowrap;">
                                                <a href="{{ $documentosUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">Ir a enlace documentos</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="padding:0 0 0 6px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="border-radius:6px;background:#1d4ed8;text-align:center;white-space:nowrap;">
                                                <a href="{{ $bitacoraPublicUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">Ir a bitacora de pruebas</a>
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
