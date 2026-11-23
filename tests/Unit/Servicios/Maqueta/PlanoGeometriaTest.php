<?php

use App\Services\Servicios\Maqueta\PlanoGeometria;

it('convierte las coordenadas del plano', function () {
    $geometria = new PlanoGeometria;
    expect($geometria->hotspot(['x' => 60, 'y' => 90, 'w' => 30, 'h' => 45]))->toEqual([20, 33, 20, 30]);
    expect($geometria->muro(0, 50, 30, 130)->toArray())->toEqual(['x' => 10, 'y' => 23, 'w' => 100, 'h' => 2.915]);
    expect($geometria->muro(1, 50, 30, 130)->toArray())->toEqual(['x' => 30, 'y' => 3, 'w' => 914 * .0032, 'h' => 100]);
});
