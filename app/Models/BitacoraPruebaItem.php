<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BitacoraPruebaItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bitacora_pruebas_items';

    protected $fillable = [
        'bitacora_pruebas_dia_id',
        'nombre_prueba',
        'institucion_exhibe',
        'ingresado_por_user_id',
    ];

    public function dia(): BelongsTo
    {
        return $this->belongsTo(BitacoraPruebaDia::class, 'bitacora_pruebas_dia_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ingresado_por_user_id');
    }
}
