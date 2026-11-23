<?php

namespace App\Services\Servicios\Maqueta;

class PlanoGeometria
{
    public const ANCHO = 914;

    public const ALTO = 583;

    private const ESCALA = 1.5;

    private const DESPLAZAMIENTO_X = 20;

    private const DESPLAZAMIENTO_Y = 27;

    private const GROSOR_MURO_VERTICAL = self::ANCHO * .0032;

    private const GROSOR_MURO_HORIZONTAL = self::ALTO * .005;

    /**
     * @param  array{x: float, y: float, w: float, h: float}  $room
     * @return array{float, float, float, float}
     */
    public function hotspot(array $room): array
    {
        return [
            $room['x'] / self::ESCALA - self::DESPLAZAMIENTO_X,
            $room['y'] / self::ESCALA - self::DESPLAZAMIENTO_Y,
            $room['w'] / self::ESCALA,
            $room['h'] / self::ESCALA,
        ];
    }

    public function muro(int $axis, float $coordinate, float $start, float $end): MuroMaqueta
    {
        return new MuroMaqueta(
            ($axis ? $coordinate : $start) - self::DESPLAZAMIENTO_X,
            ($axis ? $start : $coordinate) - self::DESPLAZAMIENTO_Y,
            $axis ? self::GROSOR_MURO_VERTICAL : $end - $start,
            $axis ? $end - $start : self::GROSOR_MURO_HORIZONTAL,
        );
    }
}
