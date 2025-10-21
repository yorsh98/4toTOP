<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Libertad extends Model
{
    use SoftDeletes;

    protected $table = 'libertad'; // manteniendo tu nombre singular
    protected $fillable = [
        'Numentregado','año','CausaAsig','UserSolicitante','UserDirigido',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /* ==== Scopes de ayuda ==== */

    // Activos (no borrados)
    public function scopeActivos($q)
    {
        return $q->whereNull('deleted_at');
    }

    // Por año
    public function scopeDelAnio($q, $anio)
    {
        return $q->where('año', $anio);
    }

    // Solo borrados del año
    public function scopeSoloBorradosDelAnio($q, $anio)
    {
        return $q->onlyTrashed()->where('año', $anio);
    }
}
