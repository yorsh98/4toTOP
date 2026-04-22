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
            ? trim($passwordOption)
            : trim((string) env('SUPERADMIN_PASSWORD', ''));
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
            if ($password === '') {
                $this->error('Debes entregar una password con --password o SUPERADMIN_PASSWORD para forzar el reseteo.');

                return self::FAILURE;
            }

            if (mb_strlen($password) < 12) {
                $this->error('La password debe tener al menos 12 caracteres para cumplir el minimo de seguridad.');

                return self::FAILURE;
            }

            $user->password = Hash::make($password);
            $this->info('Password del SuperAdmin actualizada correctamente.');
        }

        $user->save();

        $user->syncRoles([$role->name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("SuperAdmin asegurado para {$email}");

        return self::SUCCESS;
    }
}
