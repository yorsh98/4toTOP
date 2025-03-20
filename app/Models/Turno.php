<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Turno extends Model
{
    use HasFactory;
    
    // Especificar el nombre de la tabla en singular ya que así está en la migración
    protected $table = 'turno';
    
    // Asegúrate que los campos $fillable coincidan con los de la migración
    protected $fillable = [
        'TM1', 'TM2', 'TM3', 
        'TF1', 'TF2', 'TF3', 
        'TJ1', 'TJ2', 'TJ3',
        'ACDM1', 'ACDM2', 'ACDM3',
        'ACDF1', 'ACDF2', 'ACDF3',
        'ACDJ1', 'ACDJ2', 'ACDJ3',
        'FECHA'
    ];
}