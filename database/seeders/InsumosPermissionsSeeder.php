<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InsumosPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'navbar.insumos.visible',
            'navbar.insumos.editable',
            'insumos.solicitar',
            'insumos.gestionar',
            'insumos.inventario',
            'insumos.importar',
            'insumos.reportes',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
