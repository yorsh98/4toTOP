<?php

use App\Services\Servicios\Maqueta\SalaMaqueta;

it('calcula y serializa la altura según los activos con un límite', function (int $activos, float $altura) {
    $sala = new SalaMaqueta('A', 'Sala A', 0, 0, 1, 1, $activos, true, false, false, false);

    expect($sala->altura())->toEqualWithDelta($altura, 0.00001)
        ->and($sala->toArray()['altura'])->toEqualWithDelta($altura, 0.00001);
})->with([
    [0, 1.0968],
    [1, 4.0216],
    [10, 11.425],
    [25, 11.425],
]);
