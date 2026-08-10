<?php

namespace Database\Seeders;

use App\Models\Activo;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class ActivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Sector::query()->doesntExist()) {
            $this->call(SectorSeeder::class);
        }

        $activos = [
            [
                'sector' => '838',
                'codigo_inventario' => 'SERV-838-001',
                'nombre' => 'Refrigerador cocina',
                'numero_serie' => 'COC-838-001',
                'estado' => 'Operativo',
                'observaciones' => 'Equipo asignado a cocina del piso 8.',
            ],
            [
                'sector' => '811',
                'codigo_inventario' => 'SERV-811-001',
                'nombre' => 'Notebook de respaldo',
                'numero_serie' => 'BOD-811-001',
                'estado' => 'Operativo',
                'observaciones' => 'Disponible para contingencias.',
            ],
            [
                'sector' => '820',
                'codigo_inventario' => 'SERV-820-001',
                'nombre' => 'Aspiradora industrial',
                'numero_serie' => 'ASEO-820-001',
                'estado' => 'En Reparación',
                'observaciones' => 'Pendiente de revisión técnica.',
            ],
            [
                'sector' => 'HALL CENTRAL',
                'codigo_inventario' => 'SERV-HALL-001',
                'nombre' => 'Dispensador de agua',
                'numero_serie' => 'HALL-001',
                'estado' => 'Operativo',
                'observaciones' => 'Uso compartido en hall central.',
            ],
            [
                'sector' => '805',
                'codigo_inventario' => 'SERV-805-001',
                'nombre' => 'Estantería metálica',
                'numero_serie' => 'BOD-805-001',
                'estado' => 'Operativo',
                'observaciones' => 'Almacenamiento de insumos de oficina.',
            ],
        ];

        foreach ($activos as $activo) {
            $sector = Sector::query()
                ->where('identificador_mapa', $activo['sector'])
                ->firstOrFail();

            Activo::query()->updateOrCreate(
                ['codigo_inventario' => $activo['codigo_inventario']],
                [
                    'sector_id' => $sector->id,
                    'nombre' => $activo['nombre'],
                    'numero_serie' => $activo['numero_serie'],
                    'estado' => $activo['estado'],
                    'observaciones' => $activo['observaciones'],
                ]
            );
        }
    }
}
