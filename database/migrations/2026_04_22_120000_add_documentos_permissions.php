<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'navbar.documentos.visible',
        'navbar.documentos.editable',
        'documentos.ver',
        'documentos.gestionar',
        'sentencias.importar',
    ];

    public function up(): void
    {
        $tableNames = config('permission.table_names', []);

        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (! Schema::hasTable($permissionsTable) || ! Schema::hasTable($rolesTable) || ! Schema::hasTable($roleHasPermissionsTable)) {
            return;
        }

        $now = now();

        DB::table($permissionsTable)->insertOrIgnore(
            array_map(fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], self::PERMISSIONS)
        );

        $roleIds = DB::table($rolesTable)
            ->whereIn('name', ['SuperAdmin', 'AdminOperacion'])
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        if (empty($roleIds)) {
            return;
        }

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', self::PERMISSIONS)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        if (empty($permissionIds)) {
            return;
        }

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ];
            }
        }

        DB::table($roleHasPermissionsTable)->insertOrIgnore($rows);
    }

    public function down(): void
    {
        // Data migration: no destructive rollback.
    }
};
