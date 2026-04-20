<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
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

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $adminOperacion = Role::firstOrCreate([
            'name' => 'AdminOperacion',
            'guard_name' => 'web',
        ]);

        $operador = Role::firstOrCreate([
            'name' => 'Operador',
            'guard_name' => 'web',
        ]);

        $consulta = Role::firstOrCreate([
            'name' => 'Consulta',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $adminOperacion->syncPermissions([
            'navbar.inicio.visible',
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
        ]);

        $operador->syncPermissions([
            'navbar.inicio.visible',
            'navbar.oficios_libertades.visible',
            'navbar.oficios_libertades.editable',
            'navbar.turnos.visible',
            'navbar.turnos.editable',
            'navbar.guias.visible',
            'navbar.audiencias.visible',
            'navbar.audiencias.editable',
            'navbar.bitacora_pruebas.visible',
            'navbar.bitacora_pruebas.editable',
            'navbar.ausencias.visible',
            'turnos.ver',
            'turnos.editar',
            'audiencias.ver',
            'audiencias.crear',
            'audiencias.editar',
            'audiencias.monitor.ver',
            'bitacora_pruebas.ver',
            'bitacora_pruebas.crear',
            'bitacora_pruebas.editar',
            'oficios.ver',
            'oficios.crear',
            'libertades.ver',
            'libertades.crear',
            'guias.ver',
            'ausentismo.ver',
            'progra.ver',
            'sentencias.ver',
        ]);

        $consulta->syncPermissions([
            'navbar.inicio.visible',
            'navbar.turnos.visible',
            'navbar.guias.visible',
            'navbar.audiencias.visible',
            'navbar.bitacora_pruebas.visible',
            'navbar.ausencias.visible',
            'turnos.ver',
            'audiencias.ver',
            'audiencias.monitor.ver',
            'bitacora_pruebas.ver',
            'guias.ver',
            'ausentismo.ver',
            'progra.ver',
            'sentencias.ver',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
