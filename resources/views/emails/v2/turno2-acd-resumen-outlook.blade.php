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
                        @php
                            $magistradosList = is_array($magistrados ?? null) ? $magistrados : [];
                            if ($magistradosList === []) {
                                $magistradosList = [
                                    ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 1'],
                                    ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 2'],
                                    ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 3'],
                                ];
                            }

                            $funcionariosList = is_array($funcionarios ?? null) ? $funcionarios : [];
                        @endphp
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:14px;line-height:20px;border:1px solid #cbd5e1;">
                            <tr>
                                <td colspan="6" style="border:1px solid #cbd5e1;padding:12px 10px;text-align:center;font-weight:bold;font-size:28px;line-height:30px;background:#e2e8f0;color:#0f172a;">
                                    Turno 2 ACD<br>
                                    <span style="font-size:22px;line-height:26px;color:#1e3a8a;">Periodo {{ $semanaRango }}</span>
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
                            @foreach($magistradosList as $index => $magistrado)
                                <tr>
                                    @if ($index === 0)
                                        <td rowspan="{{ $tribunalRowspan }}" style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#f1f5f9;font-size:18px;line-height:22px;">{{ $tribunalOral }}</td>
                                    @endif
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $magistrado['run'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ $magistrado['nombre'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $magistrado['telefono'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">
                                        @if(($magistrado['correo'] ?? '-') !== '-')
                                            <a href="mailto:{{ $magistrado['correo'] }}" style="color:#1d4ed8;">{{ $magistrado['correo'] }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $magistrado['cargo'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">RUN</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Jefatura</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">TELEFONO</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">CORREO</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#dbeafe;">Observaciones</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $jefatura['run'] ?? '-' }}</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ $jefatura['nombre'] ?? '-' }}</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $jefatura['telefono'] ?? '-' }}</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">
                                    @if(($jefatura['correo'] ?? '-') !== '-')
                                        <a href="mailto:{{ $jefatura['correo'] }}" style="color:#1d4ed8;">{{ $jefatura['correo'] }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $jefatura['cargo'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">RUN</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;font-size:16px;line-height:20px;background:#fef3c7;color:#92400e;">Funcion de equipo 1</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">TELEFONO</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">CORREO</td>
                                <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;font-weight:bold;background:#fef3c7;">Funcion Equipo</td>
                            </tr>
                            @forelse($funcionariosList as $funcionario)
                                <tr>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $funcionario['run'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">{{ $funcionario['nombre'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $funcionario['telefono'] ?? '-' }}</td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">
                                        @if(($funcionario['correo'] ?? '-') !== '-')
                                            <a href="mailto:{{ $funcionario['correo'] }}" style="color:#1d4ed8;">{{ $funcionario['correo'] }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">{{ $funcionario['cargo'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">Sin funcionarios asignados.</td>
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
