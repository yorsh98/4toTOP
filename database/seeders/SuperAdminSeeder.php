<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    private const REQUIRED_SUPERADMIN_EMAIL = 'jtroncosor@pjud.cl';

    public function run(): void
    {
        $email = self::REQUIRED_SUPERADMIN_EMAIL;
        $name = (string) env('SUPERADMIN_NAME', 'Super Admin');
        $password = (string) env('SUPERADMIN_PASSWORD', str()->random(48));
        $forceResetPassword = (bool) env('SUPERADMIN_FORCE_RESET_PASSWORD', false);

        $superAdminRole = Role::firstOrCreate([
            'name' => 'SuperAdmin',
            'guard_name' => 'web',
        ]);

        $user = User::firstOrNew(['email' => $email]);
        $isNewUser = ! $user->exists;

        $user->name = $name;
        $user->email_verified_at = $user->email_verified_at ?? now();

        if ($isNewUser || $forceResetPassword) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $user->syncRoles([$superAdminRole->name]);
    }
}
