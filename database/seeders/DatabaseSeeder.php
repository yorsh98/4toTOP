<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            MailSignaturesSeeder::class,
            MailRecipientSeeder::class,
            ServiciosRoleSeeder::class,
            SectorSeeder::class,
            ActivoSeeder::class,
            DocumentosCatalogSeeder::class,
            VisitaCarcelCatalogSeeder::class,
        ]);

        User::query()->firstOrCreate(
            ['email' => 'local-fixture@example.invalid'],
            [
                'name' => 'Test User',
                'password' => str()->random(48),
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );
    }
}
