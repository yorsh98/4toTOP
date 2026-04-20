<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_PERMISSIONS = [
        'navbar.bitacora_pruebas.visible',
        'navbar.bitacora_pruebas.editable',
        'bitacora_pruebas.ver',
        'bitacora_pruebas.crear',
        'bitacora_pruebas.editar',
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
            ], self::NEW_PERMISSIONS)
        );

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', self::NEW_PERMISSIONS)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        $this->assignPermissionsToRole($rolesTable, $roleHasPermissionsTable, 'AdminOperacion', [
            'navbar.bitacora_pruebas.visible',
            'navbar.bitacora_pruebas.editable',
            'bitacora_pruebas.ver',
            'bitacora_pruebas.crear',
            'bitacora_pruebas.editar',
        ], $permissionIds);

        $this->assignPermissionsToRole($rolesTable, $roleHasPermissionsTable, 'Operador', [
            'navbar.bitacora_pruebas.visible',
            'navbar.bitacora_pruebas.editable',
            'bitacora_pruebas.ver',
            'bitacora_pruebas.crear',
            'bitacora_pruebas.editar',
        ], $permissionIds);

        $this->assignPermissionsToRole($rolesTable, $roleHasPermissionsTable, 'Consulta', [
            'navbar.bitacora_pruebas.visible',
            'bitacora_pruebas.ver',
        ], $permissionIds);
    }

    public function down(): void
    {
        // Forward-only migration.
    }

    private function assignPermissionsToRole(
        string $rolesTable,
        string $roleHasPermissionsTable,
        string $roleName,
        array $permissionNames,
        \Illuminate\Support\Collection $permissionIds,
    ): void {
        $roleId = DB::table($rolesTable)
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $rows = collect($permissionNames)
            ->map(fn (string $name) => $permissionIds->get($name))
            ->filter()
            ->map(fn ($permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ])
            ->values()
            ->all();

        if (! empty($rows)) {
            DB::table($roleHasPermissionsTable)->insertOrIgnore($rows);
        }
    }
};
