@extends('emails.outlook.layout')
@section('preheader', 'El 4° Tribunal te desea un feliz cumpleaños, '.($person['nombre'] ?? '').'.')
@section('kicker', 'CUMPLEAÑOS')
@section('heading', '¡Feliz cumpleaños, '.($person['nombre'] ?? '').'!')
@section('content')
<tr><td align="center" style="padding:0 20px 18px 20px;"><img src="{{ $image }}" width="600" height="300" alt="Imagen de saludo: feliz cumpleaños" border="0" style="display:block;width:600px;height:300px;border:0;"></td></tr>
<tr><td align="left" style="padding:0 24px 14px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#1F2937;mso-line-height-rule:exactly;">Este Tribunal, en nombre de todos sus funcionarios y funcionarias, te saluda muy afectuosamente y te desea un día lleno de alegría y amor junto a tus seres queridos.<br><br><strong>¡Felicidades!</strong></td></tr>
@if (count($others))
<tr><td style="padding:0 24px 18px 24px;"><table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr><td bgcolor="#EAF0F9" style="background-color:#EAF0F9;border-left:4px solid #2F5597;padding:12px 14px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1F2937;mso-line-height-rule:exactly;"><strong>Hoy también están de cumpleaños:</strong><br>
@foreach ($others as $other){{ $other['nombre'] }}, correo {{ $other['email'] }}<br>
@endforeach</td></tr></table></td></tr>
@endif
<tr><td align="left" style="padding:0 24px 8px 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;color:#1F2937;">Revisa el listado completo de cumpleaños:</td></tr>
<tr><td align="left" style="padding:0 24px 18px 24px;">@include('emails.outlook.button', ['href' => route('cumpleanos.index'), 'label' => 'Ver cumpleaños', 'fill' => '#1F3864', 'w' => 220])</td></tr>
<tr><td align="left" style="padding:0 24px 20px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#4A5568;">Si no ves la imagen, habilita la descarga de imágenes en tu correo para una correcta visualización.</td></tr>
@endsection
