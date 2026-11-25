@php
    $accentBar = $accentBar ?? '#2F5597';
    $accentText = $accentText ?? '#2F5597';
    $font = 'Arial,Helvetica,sans-serif';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<title>@yield('heading')</title>
<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<style type="text/css">
body{margin:0;padding:0;}
table{border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;}
td,th,p,li{font-family:{{ $font }};font-size:14px;line-height:21px;color:#1F2937;}
a{color:#1F5FA8;}
</style>
</head>
<body bgcolor="#EEF1F6" style="margin:0;padding:0;background-color:#EEF1F6;">
<div style="display:none;mso-hide:all;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;color:#EEF1F6;">@yield('preheader')&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#EEF1F6" style="width:100%;background-color:#EEF1F6;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
<tr><td align="center" style="padding:20px 8px;">
<table role="presentation" width="640" align="center" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="width:640px;background-color:#FFFFFF;border:1px solid #D5DCE8;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
<tr><td bgcolor="#1F3864" style="background-color:#1F3864;padding:16px 24px 14px 24px;font-family:{{ $font }};">
<span style="font-size:11px;line-height:15px;letter-spacing:1px;color:#C9D6EE;mso-line-height-rule:exactly;">PODER JUDICIAL · REPÚBLICA DE CHILE</span><br>
<span style="font-size:17px;line-height:24px;font-weight:bold;color:#FFFFFF;mso-line-height-rule:exactly;">4° Tribunal de Juicio Oral en lo Penal de Santiago</span><br>
<span style="font-size:12px;line-height:18px;color:#C9D6EE;mso-line-height-rule:exactly;">Sistema de Notificaciones 4°TOP</span>
</td></tr>
<tr><td height="4" bgcolor="{{ $accentBar }}" style="height:4px;background-color:{{ $accentBar }};font-size:1px;line-height:4px;mso-line-height-rule:exactly;">&nbsp;</td></tr>
<tr><td style="padding:20px 24px 14px 24px;font-family:{{ $font }};">
<span style="font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1px;color:{{ $accentText }};mso-line-height-rule:exactly;">@yield('kicker')</span><br>
<h1 style="margin:0;padding:0;font-family:{{ $font }};font-size:22px;line-height:28px;font-weight:bold;color:#1F3864;mso-line-height-rule:exactly;">@yield('heading')</h1>
</td></tr>
@yield('content')
<tr><td bgcolor="#F3F5F9" style="background-color:#F3F5F9;border-top:1px solid #D5DCE8;padding:14px 24px;font-family:{{ $font }};font-size:12px;line-height:18px;color:#4A5568;mso-line-height-rule:exactly;">
Correo automático del sistema 4°TOP – no responder.<br>
4° Tribunal de Juicio Oral en lo Penal de Santiago
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
