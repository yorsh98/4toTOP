<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guias extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre_completo',
        'rut',
        'email',
        'telefono1',
        'telefono2',
        'institucion'
    ];

    
}
