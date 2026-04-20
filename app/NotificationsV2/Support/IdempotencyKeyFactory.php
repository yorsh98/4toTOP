<?php

namespace App\NotificationsV2\Support;

class IdempotencyKeyFactory
{
    public static function make(string $eventCode, array $payload): string
    {
        $base = match ($eventCode) {
            'turno.asignado' => sprintf(
                'turno.asignado|%s|%s|%s|%s',
                (string) ($payload['fecha_turno'] ?? ''),
                (string) ($payload['bloque_turno'] ?? ''),
                (string) ($payload['slot_turno'] ?? ''),
                (string) ($payload['nombre_asignado'] ?? '')
            ),
            'ausentismo.resumen_diario' => sprintf(
                'ausentismo.resumen_diario|%s',
                (string) ($payload['fecha_reporte'] ?? now()->toDateString())
            ),
            'turno1.resumen_semanal' => sprintf(
                'turno1.resumen_semanal|%s|%s',
                (string) ($payload['semana_rango'] ?? ''),
                (string) ($payload['fecha_turno'] ?? now()->toDateString())
            ),
            'turno2.acd.resumen_semanal' => sprintf(
                'turno2.acd.resumen_semanal|%s|%s',
                (string) ($payload['semana_rango'] ?? ''),
                (string) ($payload['fecha_turno'] ?? now()->toDateString())
            ),
            default => $eventCode.'|'.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };

        return hash('sha256', $base);
    }
}
