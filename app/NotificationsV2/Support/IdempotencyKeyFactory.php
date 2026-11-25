<?php

namespace App\NotificationsV2\Support;

class IdempotencyKeyFactory
{
    public static function make(string $eventCode, array $payload): string
    {
        $base = match ($eventCode) {
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
