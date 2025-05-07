<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudienciaEmail extends Model
{
    protected $fillable = ['asunto', 'remitente', 'fecha', 'contenido'];
}
