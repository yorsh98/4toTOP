<?php

use Database\Seeders\InsumosPermissionsSeeder;

test('insumos permissions seeder registers permissions without creating roles', function () {
    $this->seed(InsumosPermissionsSeeder::class);

    foreach ([
        'navbar.insumos.visible',
        'navbar.insumos.editable',
        'insumos.solicitar',
        'insumos.gestionar',
        'insumos.inventario',
        'insumos.importar',
        'insumos.reportes',
    ] as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
    }

    $this->assertDatabaseMissing('roles', ['name' => 'servicios', 'guard_name' => 'web']);
});
