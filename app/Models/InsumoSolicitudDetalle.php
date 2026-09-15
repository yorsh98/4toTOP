<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoSolicitudDetalle extends Model
{
    protected $table = 'insumo_solicitud_detalles';

    protected $fillable = [
        'insumo_solicitud_id',
        'insumo_id',
        'cantidad_solicitada',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'integer',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitud::class, 'insumo_solicitud_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InsumoMovimiento::class, 'solicitud_detalle_id');
    }
}
