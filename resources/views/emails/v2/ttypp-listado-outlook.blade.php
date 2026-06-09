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
              Listado TTyPP correspondiente al dia {{ $fecha }}. Enviado por {{ $senderName }}.
            </td>
          </tr>
          <tr>
            <td style="padding:14px 16px 16px 16px;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:13px;line-height:18px;">
              <p style="margin:0 0 10px 0;"><strong>Total registros:</strong> {{ count($registros) }}</p>
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
                @forelse ($registros as $registro)
                  <tr>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->rit ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->ruc ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->tipo_audiencia ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->numero_dias ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->cantidad ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->modalidad ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->encargada_ti_mp ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->encargada_ti_d ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->encargada_zoom ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->acta ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->sala ?: '-' }}</td>
                    <td style="border:1px solid #cbd5e1;padding:6px;">{{ $registro->jueces ?: '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="12" style="border:1px solid #cbd5e1;padding:10px;text-align:center;color:#64748b;">Sin registros TTyPP para esta fecha.</td>
                  </tr>
                @endforelse
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
