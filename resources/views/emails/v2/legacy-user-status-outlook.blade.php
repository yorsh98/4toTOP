<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $subject }}</title>
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
                        <p style="margin:0 0 10px 0;">Hola {{ $userName }},</p>
                        <p style="margin:0 0 10px 0;">{{ $line }}</p>
                        @if(!empty($adminName))
                            <p style="margin:0 0 10px 0;">Gestion realizada por: {{ $adminName }}.</p>
                        @endif
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
