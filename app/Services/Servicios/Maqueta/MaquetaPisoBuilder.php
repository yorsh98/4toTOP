<?php

namespace App\Services\Servicios\Maqueta;

use App\Services\Servicios\Maqueta\Contracts\PlanoMurosRepository;

class MaquetaPisoBuilder
{
    public function __construct(
        private PlanoMurosRepository $repository,
        private PlanoGeometria $geometria,
    ) {}

    /** @param list<array<string, mixed>> $hotspots */
    public function build(int $piso, array $hotspots, ?string $seleccionado): MaquetaPiso
    {
        return new MaquetaPiso(
            $piso,
            PlanoGeometria::ANCHO,
            PlanoGeometria::ALTO,
            $this->construirSalas($hotspots, $seleccionado),
            $this->construirMuros($piso),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $hotspots
     * @return list<SalaMaqueta>
     */
    private function construirSalas(array $hotspots, ?string $seleccionado): array
    {
        return array_map(function (array $room) use ($seleccionado): SalaMaqueta {
            [$x, $y, $w, $h] = $this->geometria->hotspot($room);

            return new SalaMaqueta(
                (string) $room['identificador'],
                $room['label'],
                $x,
                $y,
                $w,
                $h,
                $room['activos_count'],
                $room['has_data'],
                $room['is_map_match'],
                $room['is_map_dimmed'],
                $seleccionado !== null && $seleccionado === (string) $room['identificador'],
            );
        }, $hotspots);
    }

    /** @return list<MuroMaqueta> */
    private function construirMuros(int $piso): array
    {
        $muros = [];
        foreach ($this->repository->muros($piso) as $axis => $lines) {
            foreach ($lines as $coordinate => $segments) {
                foreach ($segments as [$start, $end]) {
                    $muros[] = $this->geometria->muro($axis, $coordinate, $start, $end);
                }
            }
        }

        return $muros;
    }
}
