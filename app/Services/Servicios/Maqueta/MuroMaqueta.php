<?php

namespace App\Services\Servicios\Maqueta;

readonly class MuroMaqueta
{
    public function __construct(
        public float $x,
        public float $y,
        public float $w,
        public float $h,
    ) {}

    /** @return array{x: float, y: float, w: float, h: float} */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
