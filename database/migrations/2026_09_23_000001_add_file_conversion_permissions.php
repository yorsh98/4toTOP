<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['navbar.conversiones.visible', 'conversiones.ver_registro'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Role::where('guard_name', 'web')->whereIn('name', ['SuperAdmin', 'AdminOperacion'])->get() as $role) {
            $role->givePermissionTo(['navbar.conversiones.visible', 'conversiones.ver_registro']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['navbar.conversiones.visible', 'conversiones.ver_registro'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
