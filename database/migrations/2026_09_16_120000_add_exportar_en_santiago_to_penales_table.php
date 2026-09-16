<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penales', function (Blueprint $table) {
            $table->boolean('exportar_en_santiago')->default(false)->after('activo');
        });

        DB::table('penales')
            ->whereIn('nombre', ['CDP Stgo Uno', 'CDT Pudahuel Norte', 'REPAS', 'CDP Stgo Sur', 'CPF San Miguel', 'Cuartel Gral. PDI Borgoño'])
            ->update(['exportar_en_santiago' => true]);
    }

    public function down(): void
    {
        Schema::table('penales', function (Blueprint $table) {
            $table->dropColumn('exportar_en_santiago');
        });
    }
};
