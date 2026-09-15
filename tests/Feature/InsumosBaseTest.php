<?php

use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('insumos base tables are available with the required columns', function () {
    expect(Schema::hasColumns('insumos', ['codigo', 'nombre', 'unidad_medida', 'stock_minimo', 'activo']))->toBeTrue()
        ->and(Schema::hasColumns('insumo_existencias', ['insumo_id', 'sector_id', 'cantidad']))->toBeTrue()
        ->and(Schema::hasColumns('insumo_solicitudes', ['solicitante_user_id', 'estado', 'firma']))->toBeTrue()
        ->and(Schema::hasColumns('insumo_solicitud_detalles', ['insumo_solicitud_id', 'insumo_id', 'cantidad_solicitada']))->toBeTrue()
        ->and(Schema::hasColumns('insumo_movimientos', ['operacion_uuid', 'tipo', 'stock_anterior', 'stock_posterior', 'asignado_a_funcionario_id']))->toBeTrue()
        ->and(Schema::hasColumns('insumo_importaciones', ['archivo_hash', 'estado']))->toBeTrue();
});

test('insumo stock is scoped to its sector', function () {
    $insumo = Insumo::create([
        'codigo' => '141115070002',
        'nombre' => 'Papel carta',
        'unidad_medida' => 'RE',
    ]);
    $sector = Sector::factory()->create();

    InsumoExistencia::create([
        'insumo_id' => $insumo->id,
        'sector_id' => $sector->id,
        'cantidad' => 17,
    ]);

    expect($insumo->fresh()->existencias->sole()->sector->is($sector))->toBeTrue()
        ->and($insumo->fresh()->existencias->sole()->cantidad)->toBe(17)
        ->and($insumo->fresh()->stock_minimo)->toBe(15);
});

test('insumos routes require their specific permissions', function (string $route, string $permission, string $heading) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($route))
        ->assertForbidden();

    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get(route($route))
        ->assertSuccessful()
        ->assertSee($heading);
})->with([
    ['insumos.solicitudes', 'insumos.solicitar', 'Solicitudes de insumos'],
    ['insumos.gestion', 'insumos.gestionar', 'Gestión de insumos'],
    ['insumos.inventario', 'insumos.inventario', 'Inventario de insumos'],
    ['insumos.importaciones', 'insumos.importar', 'Importaciones de insumos'],
    ['insumos.reportes', 'insumos.reportes', 'Reportes de asignaciones'],
]);

test('insumos permissions are registered without creating the servicios role', function () {
    $permissions = [
        'navbar.insumos.visible',
        'navbar.insumos.editable',
        'insumos.solicitar',
        'insumos.gestionar',
        'insumos.inventario',
        'insumos.importar',
        'insumos.reportes',
    ];

    foreach ($permissions as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
    }

    $this->assertDatabaseMissing('roles', ['name' => 'servicios', 'guard_name' => 'web']);
});
