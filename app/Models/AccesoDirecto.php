<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccesoDirecto extends Model
{
    protected $table = 'accesos_directos';

    protected $fillable = ['grupo', 'nombre', 'url', 'orden', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('id');
    }

    public function host(): string
    {
        return (string) parse_url($this->url, PHP_URL_HOST);
    }

    /**
     * Dominios internos (*.pjud) que solo abren desde la red del Poder Judicial.
     * Los *.pjud.cl son públicos.
     */
    public function soloRedInterna(): bool
    {
        return str_ends_with(strtolower($this->host()), '.pjud');
    }
}
