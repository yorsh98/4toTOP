<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insumo extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'unidad_medida',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'stock_minimo' => 'integer',
        ];
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(InsumoExistencia::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InsumoMovimiento::class);
    }

    public function solicitudDetalles(): HasMany
    {
        return $this->hasMany(InsumoSolicitudDetalle::class);
    }
}
