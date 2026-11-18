<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalTestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('La cuenta de prueba solo se puede crear en el entorno local.');
        }

        $password = config('security.local_test_user_password');

        if (! is_string($password) || strlen($password) > 72 || mb_strlen(trim($password)) < 12) {
            throw new RuntimeException('Configura LOCAL_TEST_USER_PASSWORD con al menos 12 caracteres y hasta 72 bytes.');
        }

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => $password,
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );
    }
}
