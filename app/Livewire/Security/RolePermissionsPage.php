<?php

namespace App\Livewire\Security;

use App\Livewire\Security\Concerns\LogsSecurityAudit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class RolePermissionsPage extends Component
{
    use LogsSecurityAudit;

    private const NAVBAR_MODULES = [
        'inicio' => 'Inicio',
        'oficios_libertades' => 'Oficios / Libertades',
        'funcionarios' => 'Funcionarios',
        'turnos' => 'Turnos',
        'guias' => 'Guias',
        'audiencias' => 'Audiencias',
        'bitacora_pruebas' => 'Bitacora Pruebas',
        'ausencias' => 'Ausencias',
        'seguridad' => 'Seguridad',
    ];

    private const NAVBAR_LEGACY_MAP = [
        'navbar.inicio.visible' => [],
        'navbar.inicio.editable' => [],
        'navbar.oficios_libertades.visible' => ['oficios.ver', 'libertades.ver'],
        'navbar.oficios_libertades.editable' => ['oficios.crear', 'oficios.editar', 'oficios.enviar_solicitud', 'libertades.crear', 'libertades.editar'],
        'navbar.funcionarios.visible' => [],
        'navbar.funcionarios.editable' => [],
        'navbar.turnos.visible' => ['turnos.ver'],
        'navbar.turnos.editable' => ['turnos.editar'],
        'navbar.guias.visible' => ['guias.ver'],
        'navbar.guias.editable' => ['guias.editar'],
        'navbar.audiencias.visible' => ['audiencias.ver', 'audiencias.monitor.ver'],
        'navbar.audiencias.editable' => ['audiencias.crear', 'audiencias.editar', 'audiencias.estado.cambiar', 'audiencias.exportar', 'progra.mail.enviar'],
        'navbar.bitacora_pruebas.visible' => ['bitacora_pruebas.ver'],
        'navbar.bitacora_pruebas.editable' => ['bitacora_pruebas.crear', 'bitacora_pruebas.editar'],
        'navbar.ausencias.visible' => ['ausentismo.ver'],
        'navbar.ausencias.editable' => ['ausentismo.editar'],
        'navbar.seguridad.visible' => ['seguridad.roles.ver', 'seguridad.permisos.ver', 'seguridad.auditoria.ver'],
        'navbar.seguridad.editable' => ['seguridad.roles.gestionar', 'seguridad.permisos.gestionar', 'seguridad.usuarios_roles.gestionar'],
    ];

    public Role $role;

    public array $permissions = [];

    public function mount(Role $role): void
    {
        Gate::authorize('navbar.seguridad.visible');

        $this->role = $role;
        $this->permissions = $role->permissions()->pluck('name')->all();
    }

    public function save(): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $this->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $beforePermissions = $this->role->permissions()->pluck('name')->sort()->values()->all();

        $expandedPermissions = $this->expandPermissionsWithLegacy($this->permissions);
        $this->role->syncPermissions($expandedPermissions);

        if ($this->role->name === 'SuperAdmin') {
            $all = Permission::query()->pluck('name')->all();
            $this->role->syncPermissions($all);
        }

        $this->role->refresh();
        $this->permissions = $this->role->permissions()->pluck('name')->all();

        $afterPermissions = $this->role->permissions()->pluck('name')->sort()->values()->all();

        $this->logSecurityAudit(
            action: 'role.permissions.updated',
            entityType: 'role',
            entityId: (string) $this->role->id,
            before: ['permissions' => $beforePermissions],
            after: ['permissions' => $afterPermissions],
        );

        session()->flash('ok', 'Permisos del rol actualizados correctamente.');
    }

    public function render()
    {
        Gate::authorize('navbar.seguridad.visible');

        $allPermissions = Permission::query()->orderBy('name')->get();

        return view('livewire.security.role-permissions-page', [
            'navbarPermissions' => $this->buildNavbarPermissions($allPermissions),
            'advancedPermissions' => $this->buildAdvancedPermissions($allPermissions),
        ]);
    }

    private function buildNavbarPermissions(Collection $permissions): array
    {
        $indexed = $permissions->keyBy('name');
        $result = [];

        foreach (self::NAVBAR_MODULES as $moduleKey => $label) {
            $visible = "navbar.{$moduleKey}.visible";
            $editable = "navbar.{$moduleKey}.editable";

            $result[] = [
                'module' => $moduleKey,
                'label' => $label,
                'permissions' => array_values(array_filter([
                    $indexed->get($visible),
                    $indexed->get($editable),
                ])),
            ];
        }

        return $result;
    }

    private function buildAdvancedPermissions(Collection $permissions): Collection
    {
        $advanced = $permissions->filter(function (Permission $permission) {
            return ! str_starts_with($permission->name, 'navbar.');
        });

        return $advanced->groupBy(function (Permission $permission) {
            $parts = explode('.', $permission->name);

            return $parts[0] ?? 'otros';
        })->sortKeys();
    }

    private function expandPermissionsWithLegacy(array $permissions): array
    {
        $expanded = collect($permissions);

        foreach ($permissions as $permission) {
            $legacy = self::NAVBAR_LEGACY_MAP[$permission] ?? [];
            if (! empty($legacy)) {
                $expanded = $expanded->merge($legacy);
            }

            if (str_ends_with($permission, '.editable')) {
                $expanded->push(str_replace('.editable', '.visible', $permission));
            }
        }

        return $expanded->unique()->values()->all();
    }
}
