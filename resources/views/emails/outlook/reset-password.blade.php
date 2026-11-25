@extends('emails.outlook.layout')
@section('preheader', 'Restablece tu contraseña del sistema 4°TOP. El enlace vence en '.$expireMinutes.' minutos.')
@section('kicker', 'SEGURIDAD DE LA CUENTA')
@section('heading', 'Restablecer contraseña')
@section('content')
<tr><td align="left" style="padding:0 24px 12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#1F2937;mso-line-height-rule:exactly;">Hola {{ $userName }},</td></tr>
<tr><td align="left" style="padding:0 24px 18px 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1F2937;mso-line-height-rule:exactly;">Recibimos una solicitud para restablecer la contraseña de tu cuenta en el sistema 4°TOP. Para continuar, presiona el siguiente botón.</td></tr>
<tr><td align="left" style="padding:0 24px 18px 24px;">@include('emails.outlook.button', ['href' => $url, 'label' => 'Restablecer contraseña', 'fill' => '#1F3864', 'w' => 260])</td></tr>
<tr><td style="padding:0 24px 18px 24px;"><table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr><td bgcolor="#FFF4DC" style="background-color:#FFF4DC;border-left:4px solid #C77F0A;padding:10px 14px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#5C3A00;mso-line-height-rule:exactly;"><strong>Este enlace vence en {{ $expireMinutes }} minutos.</strong> Si el botón no funciona, <a href="{{ $url }}" style="color:#1F5FA8;">abre este enlace</a> en tu navegador.</td></tr></table></td></tr>
<tr><td align="left" style="padding:0 24px 22px 24px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#4A5568;">Si no solicitaste este cambio, no necesitas hacer nada: tu contraseña seguirá siendo la misma.</td></tr>
@endsection
