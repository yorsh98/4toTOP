<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penal extends Model
{
    use SoftDeletes;

    protected $table = 'penales';

    protected $fillable = ['nombre', 'activo', 'exportar_en_santiago'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'exportar_en_santiago' => 'boolean'];
    }
}
