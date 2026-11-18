<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InsumosPermissionsSeeder::class,
            MailSignaturesSeeder::class,
            MailRecipientSeeder::class,
            ServiciosRoleSeeder::class,
            SectorSeeder::class,
            ActivoSeeder::class,
            EtlActivosSeeder::class,
            DocumentosCatalogSeeder::class,
            VisitaCarcelCatalogSeeder::class,
        ]);
    }
}
