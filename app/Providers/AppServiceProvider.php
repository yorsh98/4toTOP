<?php

namespace App\Providers;

use App\Models\Guias;
use App\Models\User;
use App\Policies\GuiasPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    private const FIXED_SUPERADMIN_EMAIL = 'jtroncosor@pjud.cl';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if ($user->email === self::FIXED_SUPERADMIN_EMAIL) {
                return true;
            }

            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::policy(Guias::class, GuiasPolicy::class);

        User::saved(function (User $user): void {
            if ($user->email === self::FIXED_SUPERADMIN_EMAIL) {
                $this->syncFixedSuperAdmin($user);
            }
        });

        $this->syncFixedSuperAdminByEmailIfExists();
    }

    private function syncFixedSuperAdminByEmailIfExists(): void
    {
        try {
            if (! $this->rbacTablesReady()) {
                return;
            }

            $user = User::query()->where('email', self::FIXED_SUPERADMIN_EMAIL)->first();
            if (! $user) {
                return;
            }

            $this->syncFixedSuperAdmin($user);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function syncFixedSuperAdmin(User $user): void
    {
        if (! $this->rbacTablesReady()) {
            return;
        }

        $role = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $allPermissions = Permission::query()->pluck('name')->all();
        if (! empty($allPermissions)) {
            $role->syncPermissions($allPermissions);
        }

        if (! $user->hasRole('SuperAdmin')) {
            $user->assignRole('SuperAdmin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rbacTablesReady(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        $permissionTables = config('permission.table_names', []);
        $requiredTables = [
            $permissionTables['roles'] ?? 'roles',
            $permissionTables['permissions'] ?? 'permissions',
            $permissionTables['model_has_roles'] ?? 'model_has_roles',
            $permissionTables['role_has_permissions'] ?? 'role_has_permissions',
        ];

        foreach ($requiredTables as $table) {
            if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
