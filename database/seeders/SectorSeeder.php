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
            ['id_mapa' => 'TTyPP', 'nombre' => 'TTyPP'],
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
            Sector::firstOrCreate(
                ['identificador_mapa' => $s['id_mapa']],
                ['nombre' => $s['nombre'], 'piso' => '8VO']
            );
        }

        foreach ([
            '7MO' => ['737', '736', '735', '929', '738', '739', '712', '706', '703', '713', '714', '711', '710', '709', '708', '707', '715', '716', '717', '718', '719', '720', '705', '704', '702', '701', '721', '722', '723', '733', '732', '731', '730', '729', '728', '727', '726', '725', '724'],
            '9NO' => ['934', '935', '936', '912', '906', '903', '913', '914', '911', '910', '909', '908', '907', '915', '916', '917', '918', '919', '920', '905', '904', '902', '901', '921', '922', '923', '925', '932', '931', '930', '801', '928', '836', '926', '933', 'COMEDOR'],
        ] as $piso => $identificadores) {
            foreach ($identificadores as $identificador) {
                Sector::firstOrCreate(
                    ['identificador_mapa' => $piso[0]."_{$identificador}"],
                    ['nombre' => $identificador, 'piso' => $piso]
                );
            }

            Sector::firstOrCreate(
                ['identificador_mapa' => $piso[0].'_HALL_CENTRAL'],
                ['nombre' => 'HALL CENTRAL', 'piso' => $piso]
            );
        }

        foreach ([
            'PECERA_9' => 'Pecera 9',
            'TERESA' => 'Teresa',
            '904_OF' => '904',
            'DASH_1' => '-',
            'DASH_2' => '-',
        ] as $identificador => $nombre) {
            Sector::firstOrCreate(
                ['identificador_mapa' => "9_{$identificador}"],
                ['nombre' => $nombre, 'piso' => '9NO']
            );
        }

        foreach ([
            ['8VO', 'ADMINISTRACION_HALL', 'Adm.'],
            ['8VO', 'SERVICIOS_HALL', 'Servicios'],
            ['8VO', 'ADM', 'ADM'],
            ['8VO', 'ESPACIO_INFERIOR', 'Encargados'],
            ['7MO', '7_ESPACIO_INFERIOR', 'por designar'],
            ['7MO', '7_por designar', 'por designar'],
            ['7MO', '7_Publico', 'Publico'],
            ['8VO', 'Zona Publica', 'Zona Publica'],
            ['7MO', '7_Zona Publica', 'Zona Publica'],
            ['9NO', '9_ESPACIO_INFERIOR_1', 'Causas_2'],
            ['9NO', '9_ESPACIO_INFERIOR_2', 'Informaticos'],
            ['9NO', '9_Causas_1', 'Causas_1'],
            ['9NO', '9_Causas_3', 'Causas_3'],
            ['9NO', '9_Zona Publica', 'Zona Publica'],
        ] as [$piso, $identificador, $nombre]) {
            Sector::firstOrCreate(
                ['identificador_mapa' => $identificador],
                ['nombre' => $nombre, 'piso' => $piso]
            );
        }
    }
}
