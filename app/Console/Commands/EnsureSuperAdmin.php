<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EnsureSuperAdmin extends Command
{
    private const REQUIRED_SUPERADMIN_EMAIL = 'jtroncosor@pjud.cl';

    protected $signature = 'ensure:superadmin
        {--email= : Email del SuperAdmin}
        {--name= : Nombre del SuperAdmin}
        {--password= : Password inicial o de reemplazo}
        {--force-reset-password : Fuerza cambio de password aunque el usuario exista}';

    protected $description = 'Eleva a SuperAdmin al usuario fijo si existe';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $emailOption = (string) ($this->option('email') ?: '');
        if ($emailOption !== '' && $emailOption !== self::REQUIRED_SUPERADMIN_EMAIL) {
            $this->warn('El email de SuperAdmin es fijo por seguridad del proyecto y se ignorara el valor entregado.');
        }

        $email = self::REQUIRED_SUPERADMIN_EMAIL;
        $name = (string) ($this->option('name') ?: env('SUPERADMIN_NAME', 'Super Admin'));
        $passwordOption = $this->option('password');
        $password = is_string($passwordOption)
            ? $passwordOption
            : (string) env('SUPERADMIN_PASSWORD', str()->random(48));
        $forceResetPassword = (bool) $this->option('force-reset-password');

        $role = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $allPermissions = Permission::query()->pluck('name')->all();

        if (! empty($allPermissions)) {
            $role->syncPermissions($allPermissions);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->warn("No existe el usuario {$email}. No se creará automáticamente.");

            return self::SUCCESS;
        }

        $user->name = $name;
        $user->email_verified_at = $user->email_verified_at ?? now();

        if ($forceResetPassword) {
            $newPassword = $password;
            $user->password = Hash::make($newPassword);
            $this->warn('Password del SuperAdmin actualizada. Nueva password:');
            $this->line($newPassword);
        }

        $user->save();

        $user->syncRoles([$role->name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("SuperAdmin asegurado para {$email}");

        return self::SUCCESS;
    }
}
