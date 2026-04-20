<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'navbar.inicio.visible',
        'navbar.inicio.editable',
        'navbar.oficios_libertades.visible',
        'navbar.oficios_libertades.editable',
        'navbar.funcionarios.visible',
        'navbar.funcionarios.editable',
        'navbar.turnos.visible',
        'navbar.turnos.editable',
        'navbar.guias.visible',
        'navbar.guias.editable',
        'navbar.audiencias.visible',
        'navbar.audiencias.editable',
        'navbar.bitacora_pruebas.visible',
        'navbar.bitacora_pruebas.editable',
        'navbar.ausencias.visible',
        'navbar.ausencias.editable',
        'navbar.seguridad.visible',
        'navbar.seguridad.editable',
        'seguridad.roles.ver',
        'seguridad.roles.gestionar',
        'seguridad.permisos.ver',
        'seguridad.permisos.gestionar',
        'seguridad.usuarios_roles.gestionar',
        'seguridad.auditoria.ver',
        'turnos.ver',
        'turnos.editar',
        'audiencias.ver',
        'audiencias.crear',
        'audiencias.editar',
        'audiencias.estado.cambiar',
        'audiencias.exportar',
        'audiencias.monitor.ver',
        'bitacora_pruebas.ver',
        'bitacora_pruebas.crear',
        'bitacora_pruebas.editar',
        'oficios.ver',
        'oficios.crear',
        'oficios.editar',
        'oficios.enviar_solicitud',
        'libertades.ver',
        'libertades.crear',
        'libertades.editar',
        'guias.ver',
        'guias.editar',
        'ausentismo.ver',
        'ausentismo.editar',
        'progra.ver',
        'progra.mail.enviar',
        'sentencias.ver',
    ];

    private const ROLES = [
        'SuperAdmin',
        'AdminOperacion',
        'Operador',
        'Consulta',
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

        DB::table($rolesTable)->insertOrIgnore(
            array_map(fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], self::ROLES)
        );

        $superAdminRoleId = DB::table($rolesTable)
            ->where('name', 'SuperAdmin')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $superAdminRoleId) {
            return;
        }

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', self::PERMISSIONS)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        $rows = array_map(fn ($permissionId) => [
            'permission_id' => $permissionId,
            'role_id' => $superAdminRoleId,
        ], $permissionIds);

        if (! empty($rows)) {
            DB::table($roleHasPermissionsTable)->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Bootstrap data migration: no destructive rollback.
    }
};
