<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitaCarcelImputado extends Model
{
    use SoftDeletes;

    protected $table = 'visita_carcel_imputados';

    protected $fillable = [
        'visita_carcel_causa_id',
        'nombre',
        'rut',
        'penal',
        'delito',
        'fecha_ingreso_pp',
        'plazo_cierre',
        'ultima_rev_pp',
        'juez',
        'defensor',
        'fecha_ult_res',
        'ult_resolucion',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso_pp' => 'date',
            'plazo_cierre' => 'date',
            'ultima_rev_pp' => 'date',
            'fecha_ult_res' => 'date',
        ];
    }

    public function causa(): BelongsTo
    {
        return $this->belongsTo(VisitaCarcelCausa::class, 'visita_carcel_causa_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(VisitaCarcelImputadoHistorial::class, 'visita_carcel_imputado_id')->latest('id');
    }
}
