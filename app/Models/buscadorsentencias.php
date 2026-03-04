<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class buscadorsentencias extends Model
{
    protected $table = 'buscadorsentencias';

    protected $fillable = [
        'ruc',
        'rit',
        'ano',
        'nombre_partes',
        'materia',
        'fecha_decision',
        'glosa_decision',
        'juez',
        'instancia'
    ];

    protected $casts = [
        'fecha_decision' => 'date',
        'rit' => 'integer',
        'ano' => 'integer',
    ];
}
