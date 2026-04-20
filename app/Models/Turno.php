<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    // Especificar el nombre de la tabla en singular ya que así está en la migración
    protected $table = 'turno';

    // Asegúrate que los campos $fillable coincidan con los de la migración
    protected $fillable = [
        'TM1', 'TM2', 'TM3',
        'TF1', 'TF2', 'TF3',
        'TF1_CARGO', 'TF2_CARGO', 'TF3_CARGO',
        'TJ1', 'TJ2', 'TJ3',
        'TJ1_OBS',
        'ACDM1', 'ACDM2', 'ACDM3',
        'ACDF1', 'ACDF2', 'ACDF3',
        'ACDF1_CARGO', 'ACDF2_CARGO', 'ACDF3_CARGO',
        'ACDJ1', 'ACDJ2', 'ACDJ3',
        'FECHA',
    ];
}
