<?php

namespace App\Http\Controllers;

use App\Models\SecurityAudit;
use App\Models\User;
use App\NotificationsV2\Application\Services\LegacyUserAccountStatusNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class SeguridadController extends Controller
{
    private const NAVBAR_MODULES = [
        'inicio' => 'Inicio',
        'oficios_libertades' => 'ADM Oficios / Libertades',
        'funcionarios' => 'Funcionarios',
        'turnos' => 'Turnos',
        'guias' => 'Guias',
        'audiencias' => 'Audiencias',
        'bitacora_pruebas' => 'Bitacora Pruebas',
        'ausencias' => 'Ausencias',
        'visita_carcel' => 'Visita Carcel',
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
        'navbar.visita_carcel.visible' => ['visita_carcel.ver'],
        'navbar.visita_carcel.editable' => ['visita_carcel.gestionar'],
        'navbar.seguridad.visible' => ['seguridad.roles.ver', 'seguridad.permisos.ver', 'seguridad.auditoria.ver'],
        'navbar.seguridad.editable' => ['seguridad.roles.gestionar', 'seguridad.permisos.gestionar', 'seguridad.usuarios_roles.gestionar'],
    ];

    public function __construct(private readonly LegacyUserAccountStatusNotifier $legacyUserAccountStatusNotifier) {}

    public function rolesIndex(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('seguridad.roles-index', [
            'roles' => $roles,
        ]);
    }

    public function rolesStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:roles,name'],
        ], [
            'name.regex' => 'El nombre del rol solo admite letras, números, punto, guion y guion bajo.',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $this->logSecurityAudit(
            request: $request,
            action: 'role.created',
            entityType: 'role',
            entityId: (string) $role->id,
            before: null,
            after: [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ]
        );

        return back()->with('ok', 'Rol creado correctamente.');
    }

    public function rolesDestroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'SuperAdmin') {
            return back()->with('error', 'No se puede eliminar el rol SuperAdmin.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'No se puede eliminar un rol asignado a usuarios.');
        }

        $before = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'users_count' => $role->users()->count(),
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ];

        $role->delete();

        $this->logSecurityAudit(
            request: $request,
            action: 'role.deleted',
            entityType: 'role',
            entityId: (string) $role->id,
            before: $before,
            after: null
        );

        return back()->with('ok', 'Rol eliminado correctamente.');
    }

    public function rolePermissionsEdit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get();
        $assigned = $role->permissions()->pluck('name')->all();

        $navbarPermissions = $this->buildNavbarPermissions($permissions);
        $advancedPermissions = $this->buildAdvancedPermissions($permissions);

        return view('seguridad.role-permissions', [
            'role' => $role,
            'navbarPermissions' => $navbarPermissions,
            'advancedPermissions' => $advancedPermissions,
            'assigned' => $assigned,
        ]);
    }

    public function rolePermissionsUpdate(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $beforePermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $permissions = $data['permissions'] ?? [];
        $expandedPermissions = $this->expandPermissionsWithLegacy($permissions);
        $role->syncPermissions($expandedPermissions);

        if ($role->name === 'SuperAdmin') {
            $all = Permission::query()->pluck('name')->all();
            $role->syncPermissions($all);
        }

        $afterPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $this->logSecurityAudit(
            request: $request,
            action: 'role.permissions.updated',
            entityType: 'role',
            entityId: (string) $role->id,
            before: ['permissions' => $beforePermissions],
            after: ['permissions' => $afterPermissions]
        );

        return back()->with('ok', 'Permisos del rol actualizados correctamente.');
    }

    public function usersRolesIndex(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roles')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get();

        return view('seguridad.users-roles', [
            'users' => $users,
            'roles' => $roles,
            'q' => $q,
        ]);
    }

    public function usersSendResetPasswordLink(Request $request, User $user): RedirectResponse
    {
        if ($user->isSuspended()) {
            return back()->with('error', 'No se puede enviar reset a un usuario suspendido.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('error', __($status));
        }

        $this->logSecurityAudit(
            request: $request,
            action: 'user.reset_password_link.sent',
            entityType: 'user',
            entityId: (string) $user->id,
            before: null,
            after: ['email' => $user->email]
        );

        return back()->with('ok', 'Correo de recuperación enviado correctamente.');
    }

    public function usersSetSuspension(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'suspend' => ['required', 'boolean'],
        ]);

        $suspend = (bool) $data['suspend'];

        if ($user->id === auth()->id() && $suspend) {
            return back()->with('error', 'No puedes suspender tu propio usuario.');
        }

        $before = [
            'suspended_at' => optional($user->suspended_at)?->toDateTimeString(),
            'suspension_reason' => $user->suspension_reason,
        ];

        $user->suspended_at = $suspend ? now() : null;
        $user->suspension_reason = $suspend ? 'Suspendido por administrador' : null;
        $user->save();

        if ($suspend) {
            $user->revokeAccess();
        }

        $this->sendStatusEmail($user, $suspend ? 'suspended' : 'unsuspended');

        $after = [
            'suspended_at' => optional($user->suspended_at)?->toDateTimeString(),
            'suspension_reason' => $user->suspension_reason,
            'email' => $user->email,
        ];

        $this->logSecurityAudit(
            request: $request,
            action: $suspend ? 'user.suspended' : 'user.unsuspended',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: $after
        );

        return back()->with('ok', $suspend ? 'Usuario suspendido correctamente.' : 'Usuario reactivado correctamente.');
    }

    public function usersApprove(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio estado de aprobación.');
        }

        $before = [
            'approved_at' => optional($user->approved_at)?->toDateTimeString(),
            'approved_by' => $user->approved_by,
            'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
            'rejected_by' => $user->rejected_by,
            'rejection_note' => $user->rejection_note,
        ];

        $user->approved_at = now();
        $user->approved_by = auth()->id();
        $user->approval_note = 'Aprobado desde Seguridad';
        $user->rejected_at = null;
        $user->rejected_by = null;
        $user->rejection_note = null;
        $user->save();

        $this->sendStatusEmail($user, 'approved');

        $this->logSecurityAudit(
            request: $request,
            action: 'user.approved',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: [
                'approved_at' => optional($user->approved_at)?->toDateTimeString(),
                'approved_by' => $user->approved_by,
                'email' => $user->email,
            ]
        );

        return back()->with('ok', 'Usuario aprobado correctamente.');
    }

    public function usersReject(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio estado de aprobación.');
        }

        $before = [
            'approved_at' => optional($user->approved_at)?->toDateTimeString(),
            'approved_by' => $user->approved_by,
            'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
            'rejected_by' => $user->rejected_by,
            'rejection_note' => $user->rejection_note,
        ];

        $user->approved_at = null;
        $user->approved_by = null;
        $user->approval_note = null;
        $user->rejected_at = now();
        $user->rejected_by = auth()->id();
        $user->rejection_note = 'Rechazado desde Seguridad';
        $user->save();
        $user->revokeAccess();

        $this->sendStatusEmail($user, 'rejected');

        $this->logSecurityAudit(
            request: $request,
            action: 'user.rejected',
            entityType: 'user',
            entityId: (string) $user->id,
            before: $before,
            after: [
                'rejected_at' => optional($user->rejected_at)?->toDateTimeString(),
                'rejected_by' => $user->rejected_by,
                'rejection_note' => $user->rejection_note,
                'email' => $user->email,
            ]
        );

        return back()->with('ok', 'Usuario rechazado correctamente.');
    }

    private function sendStatusEmail(User $user, string $status): void
    {
        try {
            $this->legacyUserAccountStatusNotifier->notify($user, $status, Auth::user()?->name);
        } catch (Throwable $e) {
            Log::warning('No fue posible enviar correo de estado de cuenta.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function usersRolesUpdate(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $beforeRoles = $user->roles()->pluck('name')->sort()->values()->all();

        $roles = $data['roles'] ?? [];

        if ($user->id === auth()->id() && ! in_array('SuperAdmin', $roles, true) && $user->hasRole('SuperAdmin')) {
            return back()->with('error', 'No puedes quitarte a ti mismo el rol SuperAdmin.');
        }

        $superAdminRole = Role::query()->where('name', 'SuperAdmin')->first();
        if ($superAdminRole) {
            $currentSuperAdmins = User::role('SuperAdmin')->count();
            $removingLastSuperAdmin = $user->hasRole('SuperAdmin')
                && ! in_array('SuperAdmin', $roles, true)
                && $currentSuperAdmins <= 1;

            if ($removingLastSuperAdmin) {
                return back()->with('error', 'No se puede dejar el sistema sin al menos un SuperAdmin.');
            }
        }

        $user->syncRoles($roles);

        $afterRoles = $user->roles()->pluck('name')->sort()->values()->all();

        $this->logSecurityAudit(
            request: $request,
            action: 'user.roles.updated',
            entityType: 'user',
            entityId: (string) $user->id,
            before: ['roles' => $beforeRoles],
            after: ['roles' => $afterRoles, 'email' => $user->email]
        );

        return back()->with('ok', 'Roles de usuario actualizados correctamente.');
    }

    public function auditIndex(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));
        $entity = trim((string) $request->query('entity', ''));

        $audits = SecurityAudit::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('actor_name', 'like', "%{$q}%")
                        ->orWhere('actor_email', 'like', "%{$q}%")
                        ->orWhere('entity_id', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%");
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($entity !== '', fn ($query) => $query->where('entity_type', $entity))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $actions = SecurityAudit::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $entities = SecurityAudit::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type');

        return view('seguridad.audit-index', [
            'audits' => $audits,
            'actions' => $actions,
            'entities' => $entities,
            'q' => $q,
            'action' => $action,
            'entity' => $entity,
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

    private function logSecurityAudit(
        Request $request,
        string $action,
        string $entityType,
        ?string $entityId,
        ?array $before,
        ?array $after
    ): void {
        $actor = Auth::user();

        SecurityAudit::create([
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
