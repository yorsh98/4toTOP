<?php

namespace App\Services;

use App\Models\Ausentismo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DailyAusentismoSnapshotService
{
    public function forDate(CarbonInterface|string|null $date = null): array
    {
        $targetDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date ?? now());
        $fechaReporte = $targetDate->toDateString();

        $ausentes = Ausentismo::query()
            ->whereDate('fecha_inicio', '<=', $fechaReporte)
            ->whereDate('fecha_termino', '>=', $fechaReporte)
            ->orderBy('funcionario_nombre')
            ->get()
            ->map(fn (Ausentismo $ausentismo): array => [
                'funcionario_nombre' => $ausentismo->funcionario_nombre,
                'cargo' => $ausentismo->cargo,
                'tipo_permiso' => $ausentismo->tipo_permiso,
                'fecha_inicio' => optional($ausentismo->fecha_inicio)->format('Y-m-d'),
                'fecha_termino' => optional($ausentismo->fecha_termino)->format('Y-m-d'),
                'observacion' => $ausentismo->observacion,
            ])
            ->values()
            ->all();

        return [
            'fecha_reporte' => $fechaReporte,
            'ausentes' => $ausentes,
            'total_ausentes' => count($ausentes),
        ];
    }
}
