<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BitacoraPruebaDia extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bitacora_pruebas_dias';

    protected $fillable = [
        'rit',
        'numero_dia',
        'fecha_dia',
    ];

    protected $casts = [
        'fecha_dia' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BitacoraPruebaItem::class, 'bitacora_pruebas_dia_id');
    }
}
