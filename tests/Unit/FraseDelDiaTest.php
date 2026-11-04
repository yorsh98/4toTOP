<?php

use App\Support\FraseDelDia;
use Carbon\CarbonImmutable;

test('las frases son únicas, breves y no están vacías', function () {
    $frases = FraseDelDia::todas();

    expect($frases)->toHaveCount(60)->toEqual(array_values(array_unique($frases)));
    foreach ($frases as $frase) {
        expect(trim($frase))->not->toBeEmpty();
        expect(mb_strlen($frase))->toBeLessThanOrEqual(90);
    }
});

test('la frase se mantiene durante el día y cambia al día siguiente', function () {
    $fecha = CarbonImmutable::parse('2026-11-04 08:00', 'America/Santiago');

    expect(FraseDelDia::hoy($fecha))->toBe(FraseDelDia::hoy($fecha->setTime(23, 59)));
    expect(FraseDelDia::hoy($fecha))->not->toBe(FraseDelDia::hoy($fecha->addDay()));
});
