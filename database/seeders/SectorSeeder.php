<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            ['id_mapa' => '837', 'nombre' => '837 Erick A.'],
            ['id_mapa' => '836', 'nombre' => '836 P. Brundl'],
            ['id_mapa' => '835', 'nombre' => '835 M. Garcia'],
            ['id_mapa' => '834', 'nombre' => '929 / 834 Claudia M.'],
            ['id_mapa' => '838', 'nombre' => '838 Cocina'],
            ['id_mapa' => '839', 'nombre' => '839 Baños'],
            ['id_mapa' => '812', 'nombre' => 'SALA 803 (812)'],
            ['id_mapa' => '806', 'nombre' => 'SALA 802 (806)'],
            ['id_mapa' => 'APUBLICO', 'nombre' => 'A.PUBLICO'],
            ['id_mapa' => '803', 'nombre' => 'SALA 801 (803)'],
            ['id_mapa' => '823', 'nombre' => '823 Baño'],
            ['id_mapa' => '814', 'nombre' => '814 Baño'],
            ['id_mapa' => '811', 'nombre' => '811 Bod comput.'],
            ['id_mapa' => '810', 'nombre' => '810 Cocina'],
            ['id_mapa' => '809', 'nombre' => '809 Baño'],
            ['id_mapa' => '808', 'nombre' => '808 Baño'],
            ['id_mapa' => '807', 'nombre' => '807 Bod oficina'],
            ['id_mapa' => '815', 'nombre' => '815 Bod.'],
            ['id_mapa' => '816', 'nombre' => '816 Baño'],
            ['id_mapa' => '817', 'nombre' => '817 Baño'],
            ['id_mapa' => '818', 'nombre' => '818 Bod resma'],
            ['id_mapa' => '819', 'nombre' => '819 Bod agua'],
            ['id_mapa' => '820', 'nombre' => '820 Bod aseo'],
            ['id_mapa' => '805', 'nombre' => '805 Bod.'],
            ['id_mapa' => '804', 'nombre' => '804 Bod.'],
            ['id_mapa' => '821', 'nombre' => '821 Bod.'],
            ['id_mapa' => '822', 'nombre' => '822 Bod anillado'],
            ['id_mapa' => '802', 'nombre' => '802 Baño'],
            ['id_mapa' => '801', 'nombre' => '801 Baño'],
            ['id_mapa' => '803_BANO', 'nombre' => '803 Baño'],
            ['id_mapa' => 'HALL CENTRAL', 'nombre' => 'HALL CENTRAL'],
            ['id_mapa' => '833', 'nombre' => '833 Rene Bonnemaison'],
            ['id_mapa' => '832', 'nombre' => '832 Pedro Suarez'],
            ['id_mapa' => '831', 'nombre' => '831 Cristian Soto'],
            ['id_mapa' => '830', 'nombre' => '830 Cristian Fuentealba'],
            ['id_mapa' => '829', 'nombre' => '829'],
            ['id_mapa' => '828', 'nombre' => '828 Sala Deliberac'],
            ['id_mapa' => '827', 'nombre' => '827 Luis Werner'],
            ['id_mapa' => '826', 'nombre' => '826 Eduardo Fredes'],
            ['id_mapa' => '825', 'nombre' => '825 Sala Niños'],
            ['id_mapa' => '823_OF', 'nombre' => '823 Amelia Muñoz'],
            ['id_mapa' => 'TTYPP DEFENSA', 'nombre' => 'TTYPP DEFENSA'],
            ['id_mapa' => 'FUERA MAG 835', 'nombre' => 'FUERA MAG 835'],
        ];

        foreach ($sectors as $s) {
            Sector::updateOrCreate(
                ['identificador_mapa' => $s['id_mapa']],
                ['nombre' => $s['nombre'], 'piso' => '8VO']
            );
        }
    }
}
