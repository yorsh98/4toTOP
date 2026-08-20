<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ServiciosRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'navbar.servicios.visible',
            'navbar.servicios.editable',
            'navbar.mapa_servicios.visible',
            'navbar.mapa_servicios.editable',
            'servicios.ver',
            'servicios.crear',
            'servicios.editar',
            'servicios.eliminar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'servicios',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);
    }
}
