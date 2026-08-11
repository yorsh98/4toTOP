<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioSalaEnlace extends Model
{
    protected $table = 'audio_sala_enlaces';

    protected $fillable = [
        'anio',
        'sala',
        'url',
        'created_by',
        'updated_by',
    ];
}
