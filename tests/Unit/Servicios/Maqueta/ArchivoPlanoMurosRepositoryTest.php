<?php

use App\Services\Servicios\Maqueta\ArchivoPlanoMurosRepository;
use Tests\TestCase;

uses(TestCase::class);

it('carga los pisos disponibles', function (int $piso) {
    expect((new ArchivoPlanoMurosRepository)->muros($piso))->toHaveCount(2);
})->with([7, 8, 9]);

it('informa si el piso no existe', function () {
    (new ArchivoPlanoMurosRepository)->muros(999);
})->throws(DomainException::class, 'No existe un plano de muros para el piso 999');
