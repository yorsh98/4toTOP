<?php

test('welcome shows public services map access', function () {
    $this->get(route('welcome'))
        ->assertSuccessful()
        ->assertSee('MAPA SERVICIOS')
        ->assertSee(route('servicios.mapa-publico'), false);
});
