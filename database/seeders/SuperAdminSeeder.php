<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (Artisan::call('ensure:superadmin') !== 0) {
            throw new RuntimeException('No se pudo asignar el rol. Ejecuta php artisan ensure:superadmin para revisar el resultado.');
        }
    }
}
