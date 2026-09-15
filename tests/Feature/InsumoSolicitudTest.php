<?php

use App\Livewire\InsumosSolicitudesPage;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\InsumoSolicitud;
use App\Models\Sector;
use App\Models\User;
use App\Services\InsumoSolicitudService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createSolicitableInsumo(string $codigo): Insumo
{
    return Insumo::create([
        'codigo' => $codigo,
        'nombre' => "Insumo {$codigo}",
        'unidad_medida' => 'UN',
    ]);
}

test('a request stores multiple details without changing stock', function () {
    $user = User::factory()->create();
    $firstInsumo = createSolicitableInsumo('SOL-001');
    $secondInsumo = createSolicitableInsumo('SOL-002');
    $sector = Sector::factory()->create();
    InsumoExistencia::create(['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 10]);
    InsumoExistencia::create(['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 5]);

    $solicitud = app(InsumoSolicitudService::class)->crear([
        ['insumo_id' => $firstInsumo->id, 'cantidad' => 2],
        ['insumo_id' => $secondInsumo->id, 'cantidad' => 3],
    ], $user->id, 'Material para audiencia');

    expect($solicitud->estado)->toBe('pendiente')
        ->and($solicitud->detalles)->toHaveCount(2)
        ->and($solicitud->firma)->toBe($user->name)
        ->and(InsumoExistencia::query()->where('insumo_id', $firstInsumo->id)->value('cantidad'))->toBe(10)
        ->and(InsumoExistencia::query()->where('insumo_id', $secondInsumo->id)->value('cantidad'))->toBe(5)
        ->and(InsumoMovimiento::query()->count())->toBe(0);
});

test('request page creates a request from the cart and shows only the current users history', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.solicitar');
    $otherUser = User::factory()->create();
    $insumo = createSolicitableInsumo('SOL-003');

    $otherRequest = app(InsumoSolicitudService::class)->crear([
        ['insumo_id' => $insumo->id, 'cantidad' => 1],
    ], $otherUser->id);

    Livewire::actingAs($user)
        ->test(InsumosSolicitudesPage::class)
        ->set('cart', [$insumo->id => 2])
        ->set('observacion', 'Solicitud desde pantalla')
        ->call('solicitar')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast')
        ->assertSee('Solicitud desde pantalla')
        ->assertSee('Sala 701')
        ->assertDontSee("Solicitud #{$otherRequest->id}");

    $this->assertDatabaseHas('insumo_solicitudes', [
        'solicitante_user_id' => $user->id,
        'estado' => 'pendiente',
        'observacion' => 'Solicitud desde pantalla',
        'firma' => $user->name,
    ]);
});

test('request accepts a catalog room signature and rejects an invalid one', function () {
    $user = User::factory()->create();
    $insumo = createSolicitableInsumo('SOL-FIRMA');

    $solicitud = app(InsumoSolicitudService::class)->crear([
        ['insumo_id' => $insumo->id, 'cantidad' => 1],
    ], $user->id, null, 'Sala 803');

    expect($solicitud->firma)->toBe('Sala 803');
    expect(fn () => app(InsumoSolicitudService::class)->crear([
        ['insumo_id' => $insumo->id, 'cantidad' => 1],
    ], $user->id, null, 'Firma manipulada'))->toThrow(InvalidArgumentException::class);
});

test('request page rejects decimal quantities', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.solicitar');
    $insumo = createSolicitableInsumo('SOL-DECIMAL');

    Livewire::actingAs($user)
        ->test(InsumosSolicitudesPage::class)
        ->set('cart', [$insumo->id => 1.5])
        ->call('solicitar')
        ->assertHasErrors('cart');
});

test('catalog pagination uses Bootstrap and preserves the cart', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.solicitar');

    foreach (range(1, 11) as $number) {
        Insumo::create([
            'codigo' => "PAGE-{$number}",
            'nombre' => sprintf('Catalogo paginado %02d', $number),
            'unidad_medida' => 'UN',
        ]);
    }

    $firstInsumoId = Insumo::query()->where('codigo', 'PAGE-1')->value('id');

    Livewire::actingAs($user)
        ->test(InsumosSolicitudesPage::class)
        ->assertSee('Catalogo paginado 01')
        ->assertDontSee('Catalogo paginado 11')
        ->assertSee('Mostrando 1 a 10 de 11 insumos')
        ->assertSeeHtml('class="pagination mb-0"')
        ->call('agregar', $firstInsumoId)
        ->call('nextPage', 'catalogoPage')
        ->assertSee('Catalogo paginado 11')
        ->assertSet("cart.{$firstInsumoId}", 1)
        ->set('search', '11')
        ->assertSee('Catalogo paginado 11')
        ->assertDontSeeHtml("wire:key=\"catalogo-{$firstInsumoId}\"");
});

test('only the owner can cancel a pending or partial request', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $insumo = createSolicitableInsumo('SOL-004');
    $service = app(InsumoSolicitudService::class);
    $solicitud = $service->crear([['insumo_id' => $insumo->id, 'cantidad' => 1]], $owner->id);

    expect(fn () => $service->cancelar($solicitud, $otherUser->id))->toThrow(AuthorizationException::class);

    $service->cancelar($solicitud, $owner->id);

    expect($solicitud->fresh()->estado)->toBe('cancelada');
});

test('completed requests cannot be cancelled', function () {
    $user = User::factory()->create();
    $insumo = createSolicitableInsumo('SOL-005');
    $solicitud = InsumoSolicitud::create([
        'solicitante_user_id' => $user->id,
        'estado' => 'completada',
    ]);

    expect(fn () => app(InsumoSolicitudService::class)->cancelar($solicitud, $user->id))
        ->toThrow(DomainException::class);
});
