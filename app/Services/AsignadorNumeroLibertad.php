<?php

namespace App\Services;

use App\Models\Libertad;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class AsignadorNumeroLibertad
{
    /**
     * Asigna un número para el año dado.
     * Si existe un Libertad soft-deleted de ese año, lo reutiliza (restore).
     * Si no, crea un nuevo correlativo (max+1).
     *
     * @param  int    $anio
     * @param  array  $payload  Campos para llenar (CausaAsig, UserSolicitante, UserDirigido, etc.)
     * @param  int    $maxRetries Reintentos por colisión de índice único (concurrencia)
     * @return \App\Models\Libertad
     *
     * @throws \Throwable
     */
    public function asignar(int $anio, array $payload, int $maxRetries = 3): Libertad
    {
        $intentos = 0;

        inicio:
        try {
            return DB::transaction(function () use ($anio, $payload) {
                // (A) Buscar candidato soft-deleted del mismo año (el más antiguo borrado)
                $candidato = Libertad::onlyTrashed()
                    ->where('año', $anio)
                    ->orderBy('deleted_at', 'asc')
                    ->first();

                if ($candidato) {
                    // Restaurar y actualizar datos
                    $candidato->restore();
                    $candidato->fill($payload)->save();

                    return $candidato;
                }

                // (B) Calcular siguiente correlativo entre activos del año
                $max = Libertad::activos()
                    ->where('año', $anio)
                    ->max('Numentregado');

                $siguiente = $max ? ($max + 1) : 1;

                // Crear nuevo
                $nuevo = new Libertad();
                $nuevo->Numentregado   = $siguiente;
                $nuevo->año            = $anio;
                $nuevo->CausaAsig      = $payload['CausaAsig']      ?? '';
                $nuevo->UserSolicitante= $payload['UserSolicitante']?? '';
                $nuevo->UserDirigido   = $payload['UserDirigido']   ?? '';
                $nuevo->save(); // protegido por índice único compuesto (solo activos)

                return $nuevo;
            }, 3); // 3 intentos de transacción interna (deadlocks)
        } catch (Throwable $e) {
            // Si chocamos contra la unicidad por carrera, reintentamos unas veces
            if ($this->esColisionUnicidad($e) && $intentos < $maxRetries) {
                $intentos++;
                goto inicio;
            }
            throw $e;
        }
    }

    /**
     * Detecta si el Throwable corresponde a violación de índice único (MySQL 1062).
     */
    protected function esColisionUnicidad(Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'Integrity constraint violation')
            || str_contains($msg, 'Duplicate entry')
            || str_contains($msg, '1062');
    }
}
