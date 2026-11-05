<?php

use App\Livewire\BuscadorSentenciasTabla;
use App\Livewire\LibertadTabla;
use App\Livewire\MonitorAudiencias;
use App\Livewire\OficioTabla;
use App\Livewire\TablaGuias;
use App\Models\Audiencia;
use App\Models\Oficio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('protects server state and bounds public oficio and libertad tables', function (string $class) {
    $component = Livewire::test($class, ['modo' => 'vista']);
    foreach (['modo' => 'full', 'editingId' => 1, 'verEliminados' => true, 'solicitantes' => []] as $property => $value) {
        expect(fn () => $component->set($property, $value))->toThrow(Exception::class, 'Cannot update locked property');
    }

    $component->call('toggleEliminados')->assertSet('verEliminados', false)
        ->set('perPage', 10000)->assertSet('perPage', 10)
        ->set('sortField', 'invalid')->assertSet('sortField', 'id')
        ->set('sortDirection', 'invalid')->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'updated_at')->assertSet('sortField', 'updated_at')
        ->set('perPage', 25)->assertSet('perPage', 25);
})->with([[OficioTabla::class], [LibertadTabla::class]]);

it('bounds sentence table ordering and pagination while keeping valid choices', function () {
    Livewire::test(BuscadorSentenciasTabla::class)
        ->set('sortField', 'invalid')->assertSet('sortField', 'fecha_decision')
        ->set('sortDirection', 'invalid')->assertSet('sortDirection', 'desc')
        ->set('perPage', 10000)->assertSet('perPage', 10)
        ->set('perPage', 25)->assertSet('perPage', 25)
        ->call('sortBy', 'rit')->assertSet('sortField', 'rit');
});

it('bounds the public directory and keeps valid sorting', function () {
    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->set('perPage', 10000)->assertSet('perPage', 10)
        ->set('perPage', 25)->assertSet('perPage', 25)
        ->call('sortBy', 'email')->assertSet('sortField', 'email');
});

it('rejects arbitrary audience states and accepts the supported transitions', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $audiencia = Audiencia::create([
        'fecha' => today()->toDateString(), 'rit' => '123-2026', 'ruc' => '12345678-9',
        'sala' => '801', 'hora_inicio' => '10:00', 'tipo_audiencia' => 'Audiencia Corta',
        'estado' => 'POR_REALIZARSE', 'anfitrion' => 'Ana', 'encargado_causa' => 'Causa', 'acta' => 'Acta', 'JuezR' => 'Juez',
        'acusados' => [['nombre_completo' => 'Persona', 'situacion' => 'Libre']],
    ]);
    $component = Livewire::actingAs($user)->test(MonitorAudiencias::class);
    expect(fn () => $component->set('currentTypeIndex', 9))->toThrow(Exception::class, 'Cannot update locked property');
    $component->set('rowsPerView', 100)->assertSet('rowsPerView', 50)
        ->call('actualizarEstadoAudienciasCortas', 'INVALID')->assertSuccessful();
    expect($audiencia->fresh()->estado)->toBe('POR_REALIZARSE');
    $component->call('actualizarEstadoAudienciasCortas', 'EN_CURSO')->assertSuccessful();
    expect($audiencia->fresh()->estado)->toBe('EN_CURSO');
});

it('keeps editing an oficio whose solicitante is no longer in the catalog', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $oficio = Oficio::create([
        'Numentregado' => 1,
        'año' => (int) now()->year,
        'CausaAsig' => 'ABC-1',
        'UserSolicitante' => 'Funcionario Antiguo',
        'UserDirigido' => 'Destino',
    ]);

    Livewire::actingAs($user)
        ->test(OficioTabla::class, ['modo' => 'full'])
        ->call('edit', $oficio->id)
        ->set('editUserDirigido', 'Otro Destino')
        ->call('update')
        ->assertHasNoErrors();

    expect($oficio->fresh()->UserDirigido)->toBe('Otro Destino');
});

it('rejects a new solicitante outside the catalog when editing an oficio', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $oficio = Oficio::create([
        'Numentregado' => 2,
        'año' => (int) now()->year,
        'CausaAsig' => 'ABC-2',
        'UserSolicitante' => 'Funcionario Antiguo',
        'UserDirigido' => 'Destino',
    ]);

    Livewire::actingAs($user)
        ->test(OficioTabla::class, ['modo' => 'full'])
        ->call('edit', $oficio->id)
        ->set('editUserSolicitante', 'Persona Inventada')
        ->call('update')
        ->assertHasErrors(['editUserSolicitante']);
});
