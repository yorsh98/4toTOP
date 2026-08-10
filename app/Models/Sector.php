<?php

namespace App\Models;

use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'piso',
        'identificador_mapa',
    ];

    public function activos(): HasMany
    {
        return $this->hasMany(Activo::class);
    }
}
