<?php

use App\Models\Funcionario;
use App\Models\Oficio;

test('index lists only allowed roles as solicitantes', function () {
    Funcionario::create([
        'nombre' => 'Jueza Excluida',
        'rol' => 'juez',
        'email' => 'jueza@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Jefatura Incluida',
        'rol' => 'jefatura',
        'email' => 'jefatura@example.cl',
        'fecha_cumpleanos' => '1981-02-02',
    ]);

    $response = $this->get(route('SistOficioLibertades.index'));

    $response->assertSuccessful();
    $response->assertSee('Jefatura Incluida');
    $response->assertDontSee('Jueza Excluida');
});

test('store rejects solicitante outside configured context', function () {
    Funcionario::create([
        'nombre' => 'Juez No Permitido',
        'rol' => 'juez',
        'email' => 'juez-no@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Jefatura Permitida',
        'rol' => 'jefatura',
        'email' => 'jefatura-ok@example.cl',
        'fecha_cumpleanos' => '1982-03-03',
    ]);

    $response = $this->post(route('SistOficioLibertades.store'), [
        'tipo' => 'oficio',
        'CausaAsig' => 'A1-2026',
        'UserSolicitante' => 'Juez No Permitido',
        'UserDirigido' => 'Prueba motivo',
    ]);

    $response->assertSessionHasErrors('UserSolicitante');
});

test('store accepts solicitante in configured context', function () {
    Funcionario::create([
        'nombre' => 'Jefatura Permitida',
        'rol' => 'jefatura',
        'email' => 'jefatura-permitida@example.cl',
        'fecha_cumpleanos' => '1982-03-03',
    ]);

    $response = $this->post(route('SistOficioLibertades.store'), [
        'tipo' => 'oficio',
        'CausaAsig' => 'B1-2026',
        'UserSolicitante' => 'Jefatura Permitida',
        'UserDirigido' => 'Prueba motivo',
    ]);

    $response->assertRedirect(route('SistOficioLibertades.index'));
    $response->assertSessionHas('success');

    $registro = Oficio::query()->latest('id')->first();

    expect($registro)->not->toBeNull();
    expect($registro->UserSolicitante)->toBe('Jefatura Permitida');
});
