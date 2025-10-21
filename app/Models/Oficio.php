<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oficio extends Model
{
   use HasFactory;
   protected $table = 'Oficio';

   protected $fillable = [
    'Numentregado',
    'año',
    'CausaAsig',
    'UserSolicitante',
    'UserDirigido'
   ];
}
