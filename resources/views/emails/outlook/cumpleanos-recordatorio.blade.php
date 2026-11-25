@extends('emails.outlook.layout')
@section('preheader', 'Hoy ('.$date->format('d-m-Y').') cumple años: '.collect($birthdays)->pluck('nombre')->implode(', ').'.')
@section('kicker', 'RECORDATORIO DE CUMPLEAÑOS')
@section('heading', count($birthdays) === 1 ? 'Hoy está de cumpleaños un compañero' : 'Hoy están de cumpleaños '.count($birthdays).' compañeros')
@section('content')
<tr><td align="center" style="padding:0 20px 18px 20px;"><img src="{{ $image }}" width="600" height="300" alt="Imagen de recordatorio: cumpleaños de hoy" border="0" style="display:block;width:600px;height:300px;border:0;"></td></tr>
<tr><td align="left" style="padding:0 24px 12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1F2937;mso-line-height-rule:exactly;"><strong>Estimado(a) funcionario(a):</strong><br>Le recordamos que hoy, <strong>{{ $date->format('d-m-Y') }}</strong>, {{ count($birthdays) === 1 ? 'se encuentra de cumpleaños nuestro(a) querido(a) compañero(a):' : 'se encuentran de cumpleaños nuestros(as) queridos(as) compañeros(as):' }}</td></tr>
<tr><td style="padding:0 24px 14px 24px;"><table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr><td bgcolor="#EAF0F9" style="background-color:#EAF0F9;border-left:4px solid #2F5597;padding:12px 14px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1F2937;mso-line-height-rule:exactly;">
@foreach ($birthdays as $birthday)<strong style="font-size:16px;color:#1F3864;">{{ $birthday['nombre'] }}</strong><br>Correo: {{ $birthday['email'] }}@if (! $loop->last)<br><br>@endif
@endforeach</td></tr></table></td></tr>
<tr><td align="left" style="padding:0 24px 14px 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#1F2937;">Para que no se olvide de saludar en este día tan especial.<br>Saluda atentamente, el 4° Tribunal de Juicio Oral en lo Penal de Santiago.</td></tr>
@if (count($upcoming))
<tr><td style="padding:0 24px 14px 24px;"><table role="presentation" width="592" border="0" cellpadding="0" cellspacing="0" style="width:592px;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tr><td bgcolor="#F3F5F9" style="background-color:#F3F5F9;border-left:4px solid #8A94A6;padding:12px 14px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#1F2937;mso-line-height-rule:exactly;"><strong>Próximos cumpleaños</strong><br>
@foreach ($upcoming as $next){{ $next['nombre'] }} — {{ $next['fecha'] }}<br>
@endforeach</td></tr></table></td></tr>
@endif
<tr><td align="left" style="padding:0 24px 18px 24px;">@include('emails.outlook.button', ['href' => route('cumpleanos.index'), 'label' => 'Ver listado de cumpleaños', 'fill' => '#1F3864', 'w' => 260])</td></tr>
<tr><td align="left" style="padding:0 24px 20px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#4A5568;">Si no ves la imagen, habilita la descarga de imágenes en tu correo para una correcta visualización.</td></tr>
@endsection
