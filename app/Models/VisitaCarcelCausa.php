<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitaCarcelCausa extends Model
{
    use SoftDeletes;

    protected $table = 'visita_carcel_causas';

    protected $fillable = [
        'ruc',
        'rit',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function imputados(): HasMany
    {
        return $this->hasMany(VisitaCarcelImputado::class, 'visita_carcel_causa_id');
    }
}
