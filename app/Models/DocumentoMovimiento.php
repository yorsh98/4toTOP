<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoMovimiento extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'documento_id',
        'user_id',
        'accion',
        'detalle',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['detalle' => 'array'];
    }
}
