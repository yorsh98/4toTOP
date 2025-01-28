<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Funcionario extends Model
{
    
        use HasFactory;
    
        protected $fillable = ['nombre'];
    
}
