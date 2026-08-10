<?php

test('removed legacy routes are not available', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    '/form',
    '/progra/mostrar',
]);
