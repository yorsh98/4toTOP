<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penal extends Model
{
    protected $table = 'penales';

    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
