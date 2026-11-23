<?php

use App\Services\Servicios\Maqueta\Contracts\PlanoMurosRepository;
use App\Services\Servicios\Maqueta\MaquetaPisoBuilder;
use App\Services\Servicios\Maqueta\PlanoGeometria;

it('construye salas y todos los segmentos sin consultar la base de datos', function () {
    $repository = new class implements PlanoMurosRepository
    {
        public function muros(int $piso): array
        {
            return [[40 => [[20, 100], [120, 150]]], [50 => [[30, 80]]]];
        }
    };
    $room = ['identificador' => 'A', 'label' => 'Sala A', 'x' => 60, 'y' => 90, 'w' => 30, 'h' => 45, 'activos_count' => 3, 'has_data' => true, 'is_map_match' => true, 'is_map_dimmed' => false];
    $dimmed = array_replace($room, ['identificador' => 'B', 'is_map_match' => false, 'is_map_dimmed' => true]);
    $model = (new MaquetaPisoBuilder($repository, new PlanoGeometria))->build(7, [$room, $dimmed], 'A');
    expect($model->muros)->toHaveCount(3)
        ->and($model->salas[0]->seleccionada)->toBeTrue()
        ->and($model->salas[0]->coincide)->toBeTrue()
        ->and($model->salas[0]->activos)->toBe(3)
        ->and($model->salas[1]->atenuada)->toBeTrue()
        ->and($model->salas[1]->seleccionada)->toBeFalse();
});
