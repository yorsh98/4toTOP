<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoMovimiento extends Model
{
    protected $table = 'insumo_movimientos';

    protected $fillable = [
        'operacion_uuid',
        'insumo_id',
        'sector_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_posterior',
        'asignado_a_user_id',
        'asignado_a_funcionario_id',
        'solicitud_detalle_id',
        'importacion_id',
        'created_by_user_id',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'stock_anterior' => 'integer',
            'stock_posterior' => 'integer',
        ];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a_user_id');
    }

    public function asignadoFuncionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'asignado_a_funcionario_id');
    }

    public function solicitudDetalle(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitudDetalle::class, 'solicitud_detalle_id');
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(InsumoImportacion::class, 'importacion_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
