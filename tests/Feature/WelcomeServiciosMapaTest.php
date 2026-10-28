<?php

test('welcome shows public services map access', function () {
    $this->get(route('welcome'))
        ->assertSuccessful()
        ->assertSee('Mapa de servicios')
        ->assertSee(route('servicios.mapa-publico'), false);
});
