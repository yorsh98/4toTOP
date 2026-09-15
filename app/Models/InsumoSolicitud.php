<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoSolicitud extends Model
{
    protected $table = 'insumo_solicitudes';

    protected $fillable = [
        'solicitante_user_id',
        'estado',
        'observacion',
        'firma',
        'rechazada_por_user_id',
        'motivo_rechazo',
    ];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_user_id');
    }

    public function rechazadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rechazada_por_user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InsumoSolicitudDetalle::class);
    }
}
