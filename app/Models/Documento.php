<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'categoria',
        'nombre',
        'archivo',
        'orden',
        'activo',
        'publico',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'publico' => 'boolean',
        ];
    }

    public function movimientos()
    {
        return $this->hasMany(DocumentoMovimiento::class)->latest();
    }

    public function url(): string
    {
        return '/storage/'.ltrim($this->archivo, '/');
    }
}
