<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TtyppRegistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ttypp_registros';

    protected $fillable = [
        'rit',
        'ruc',
        'tipo_audiencia',
        'numero_dias',
        'cantidad',
        'modalidad',
        'encargada_ti_mp',
        'encargada_ti_d',
        'encargada_zoom',
        'acta',
        'sala',
        'jueces',
        'fecha_asignacion',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
    ];
}
