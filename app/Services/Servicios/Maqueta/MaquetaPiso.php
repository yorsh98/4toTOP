<?php

namespace App\Services\Servicios\Maqueta;

readonly class MaquetaPiso
{
    /**
     * @param  list<SalaMaqueta>  $salas
     * @param  list<MuroMaqueta>  $muros
     */
    public function __construct(
        public int $piso,
        public int $ancho,
        public int $alto,
        public array $salas,
        public array $muros,
    ) {}

    /** @return array{piso: int, ancho: int, alto: int, salas: list<array>, muros: list<array>} */
    public function toArray(): array
    {
        return [
            'piso' => $this->piso,
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'salas' => array_map(fn (SalaMaqueta $sala): array => $sala->toArray(), $this->salas),
            'muros' => array_map(fn (MuroMaqueta $muro): array => $muro->toArray(), $this->muros),
        ];
    }
}
