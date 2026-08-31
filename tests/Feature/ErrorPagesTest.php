<?php

use Symfony\Component\HttpKernel\Exception\HttpException;

dataset('error pages', [
    [401, 'Debes iniciar sesión para continuar.'],
    [403, 'No tienes autorización para ver esta página.'],
    [404, 'No encontramos la página solicitada.'],
    [419, 'Tu sesión venció por seguridad.'],
    [429, 'Se recibieron demasiadas solicitudes.'],
    [500, 'No fue posible completar esta solicitud.'],
    [503, 'El servicio no está disponible temporalmente.'],
]);

test('error page renders its status and message', function (int $code, string $title) {
    $html = view("errors.{$code}")->render();

    expect($html)
        ->toContain("HTTP {$code}")
        ->toContain('Registro de navegación')
        ->toContain($title)
        ->toContain('Volver al inicio');
})->with('error pages');

test('fallback error pages render the exception status', function (string $view, int $code) {
    $html = view($view, [
        'exception' => new HttpException($code),
    ])->render();

    expect($html)->toContain("HTTP {$code}");
})->with([
    ['errors.4xx', 418],
    ['errors.5xx', 502],
]);
