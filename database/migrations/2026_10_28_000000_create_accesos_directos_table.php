<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_directos', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 80);
            $table->string('nombre', 120);
            $table->string('url', 500);
            $table->unsignedInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['activo', 'orden']);
        });

        // Los enlaces que estaban escritos en resources/views/acc.blade.php
        $accesos = [
            ['Sistemas judiciales', 'Gestión Penal 1', 'http://gestionpenal.reformaprocesal.pjud/webGestion/login.jsf'],
            ['Sistemas judiciales', 'Gestión Penal 2', 'http://gestionpenal2.reformaprocesal.pjud/webGestion/login.jsf'],
            ['Sistemas judiciales', 'Gestión Penal 3', 'http://gestionpenal3.reformaprocesal.pjud/webGestion/login.jsf'],
            ['Sistemas judiciales', 'Monito Web 1', 'http://monitoweb.srcei.cl/monito'],
            ['Sistemas judiciales', 'Monito Web 2', 'https://monitoweb2.srcei.cl/monito'],
            ['Sistemas judiciales', 'Suspensión de abogados', 'http://www.reformaprocesal.pjud/DagWeb/Console/DagConsoleWeb.vbd'],
            ['Sistemas judiciales', 'Agregar suspensión de abogado', 'http://prod.intranet.pjud/suspension_abogados/'],
            ['Sistemas judiciales', 'Portal de órdenes y contraórdenes', 'https://repoupenal.pjud.cl/'],
            ['Sistemas judiciales', 'Consulta integrada de causas UNIJUD', 'https://consultaintegrada.pjud.cl/loginMinistry'],
            ['Sistemas judiciales', 'Buscador unificado de sentencias PJUD', 'https://juris.pjud.cl/'],
            ['Administrativos PJUD', 'Intranet PJUD', 'http://www2.intranet.pjud/'],
            ['Administrativos PJUD', 'Bienestar', 'https://bienestar.pjud.cl'],
            ['Administrativos PJUD', 'RRHH', 'http://www2.recursoshumanos.intranet.pjud/'],
            ['Administrativos PJUD', 'Personas', 'https://personas.pjud.cl/portalpersonassrh/servlet/com.portalpersonas.login'],
            ['Administrativos PJUD', 'Mesa de ayuda (requerimientos)', 'http://mesaayuda.intranet.pjud/'],
            ['Técnicos y finanzas', 'Costas Judiciales', 'https://recaudacion.bancoestado.cl/CostasJudiciales/Default.aspx'],
            ['Técnicos y finanzas', 'BancoEstado instituciones públicas', 'https://www.bancoestado.cl/content/bancoestado-public/cl/es/home/inicio---bancoestado-instituciones-publicas-.html#/'],
            ['Técnicos y finanzas', 'Cuenta Corriente', 'http://www.ctacte.pjud/CTACTEWEB/jsp/Login/Login.jsp'],
            ['Técnicos y finanzas', 'CGU', 'http://www.cgu.pjud/cgu90mn-pjud/servlet/hlogin'],
            ['Técnicos y finanzas', 'Portal Sovos', 'https://portal-cl.sovos.com/'],
            ['Técnicos y finanzas', 'Finanzas', 'http://sgf-finanzas.apps.ocp.pjud/#/pages/login'],
            ['Técnicos y finanzas', 'Mercado Público', 'https://www.mercadopublico.cl/Home'],
            ['Técnicos y finanzas', 'Consulta de facturas electrónicas (CorreosChile)', 'https://dte.correos.cl/ConsultaFacturaElectronica/vista/Login.aspx'],
            ['Técnicos y finanzas', 'SII valores y fechas', 'https://www.sii.cl/valores_y_fechas/index_valores_y_fechas.html'],
            ['Técnicos y finanzas', 'SII Situación tributaria', 'https://zeus.sii.cl/cvc/stc/stc.html'],
            ['Nubes y archivos', 'Cloud PJUD', 'https://cloud.pjud.cl/'],
            ['Nubes y archivos', 'Grabaciones PJUD', 'https://grabaciones.pjud.cl/index.php/login'],
            ['Nubes y archivos', 'Good Tape', 'https://goodtape.io/'],
            ['Nubes y archivos', 'OneDrive', 'https://pjudcl-my.sharepoint.com/'],
            ['Nubes y archivos', 'WeTransfer', 'https://wetransfer.com/'],
        ];

        $now = now();
        DB::table('accesos_directos')->insert(array_map(fn (array $acceso, int $i) => [
            'grupo' => $acceso[0],
            'nombre' => $acceso[1],
            'url' => $acceso[2],
            'orden' => $i + 1,
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $accesos, array_keys($accesos)));
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_directos');
    }
};
