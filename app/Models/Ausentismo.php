<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ausentismo extends Model
{
    use HasFactory;

    protected $fillable = [
        'funcionario_nombre',
        'cargo',
        'fecha_inicio',
        'fecha_termino',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio'  => 'date:Y-m-d',
        'fecha_termino' => 'date:Y-m-d',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];
}
