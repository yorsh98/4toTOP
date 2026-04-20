<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'run',
        'rol',
        'email',
        'telefono',
        'fecha_cumpleanos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cumpleanos' => 'date',
        ];
    }

    public static function roleOptions(): array
    {
        $roles = config('funcionarios.roles', []);

        return is_array($roles) ? $roles : [];
    }

    public function getRolLabelAttribute(): string
    {
        return self::roleOptions()[$this->rol] ?? $this->rol;
    }
}
