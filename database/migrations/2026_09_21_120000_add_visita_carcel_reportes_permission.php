<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names', []);
        $permissions = $tables['permissions'] ?? 'permissions';
        $roles = $tables['roles'] ?? 'roles';
        $rolePermissions = $tables['role_has_permissions'] ?? 'role_has_permissions';

        if (! Schema::hasTable($permissions) || ! Schema::hasTable($roles) || ! Schema::hasTable($rolePermissions)) {
            return;
        }

        DB::table($permissions)->insertOrIgnore([
            'name' => 'visita_carcel.reportes',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table($permissions)->where('name', 'visita_carcel.reportes')->where('guard_name', 'web')->value('id');
        $superAdminId = DB::table($roles)->where('name', 'SuperAdmin')->where('guard_name', 'web')->value('id');

        if ($permissionId && $superAdminId) {
            DB::table($rolePermissions)->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $superAdminId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Forward-only permission catalog.
    }
};
