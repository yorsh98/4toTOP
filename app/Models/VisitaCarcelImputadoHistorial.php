<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitaCarcelImputadoHistorial extends Model
{
    public $timestamps = false;

    protected $fillable = ['visita_carcel_imputado_id', 'user_id', 'archivo', 'cambios'];

    protected function casts(): array
    {
        return ['cambios' => 'array', 'created_at' => 'datetime'];
    }

    public function imputado(): BelongsTo
    {
        return $this->belongsTo(VisitaCarcelImputado::class, 'visita_carcel_imputado_id')->withTrashed();
    }
}
