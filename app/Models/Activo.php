<?php

namespace App\Models;

use Database\Factories\ActivoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activo extends Model
{
    /** @use HasFactory<ActivoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'sector_id',
        'codigo_inventario',
        'numero_serie',
        'estado',
        'observaciones',
    ];

    protected $attributes = [
        'estado' => 'Operativo',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
