<?php

namespace App\Services\Servicios\Maqueta;

readonly class SalaMaqueta
{
    private const ALTURA_UNIDAD = 9.14;

    private const ALTURA_VACIA = .12;

    private const ALTURA_BASE = .35;

    private const ALTURA_POR_ACTIVO = .09;

    private const MAXIMO_ACTIVOS = 10;

    public function __construct(
        public string $identificador,
        public string $label,
        public float $x,
        public float $y,
        public float $w,
        public float $h,
        public int $activos,
        public bool $conInventario,
        public bool $coincide,
        public bool $atenuada,
        public bool $seleccionada,
    ) {}

    public function altura(): float
    {
        $altura = $this->activos > 0
            ? self::ALTURA_BASE + min($this->activos, self::MAXIMO_ACTIVOS) * self::ALTURA_POR_ACTIVO
            : self::ALTURA_VACIA;

        return $altura * self::ALTURA_UNIDAD;
    }

    /** @return array<string, string|float|int|bool> */
    public function toArray(): array
    {
        return [...get_object_vars($this), 'altura' => $this->altura()];
    }
}
