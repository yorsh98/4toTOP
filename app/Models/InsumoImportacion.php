<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoImportacion extends Model
{
    protected $table = 'insumo_importaciones';

    protected $fillable = [
        'archivo_nombre',
        'archivo_hash',
        'fecha_stock',
        'imported_by_user_id',
        'estado',
        'filas_leidas',
        'insumos_creados',
        'saldos_actualizados',
        'sin_cambios',
        'advertencias',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'advertencias' => 'array',
            'fecha_stock' => 'date',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InsumoMovimiento::class, 'importacion_id');
    }
}
