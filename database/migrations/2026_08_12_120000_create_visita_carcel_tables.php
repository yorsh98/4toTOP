<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visita_carcel_causas', function (Blueprint $table) {
            $table->id();
            $table->string('ruc');
            $table->string('rit');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('rit');
            $table->boolean('deleted_at_is_null')->virtualAs('IF(deleted_at IS NULL, 1, 0)');
            $table->unique(['ruc', 'deleted_at_is_null'], 'visita_carcel_causas_ruc_active_unique');
        });

        Schema::create('visita_carcel_imputados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visita_carcel_causa_id')->constrained('visita_carcel_causas');
            $table->string('nombre');
            $table->string('rut', 20)->index();
            $table->string('penal');
            $table->text('delito');
            $table->date('fecha_ingreso_pp')->index();
            $table->date('plazo_cierre')->nullable();
            $table->date('ultima_rev_pp')->nullable();
            $table->string('juez')->nullable();
            $table->string('defensor')->nullable();
            $table->date('fecha_ult_res')->nullable();
            $table->text('ult_resolucion')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('visita_carcel_imputado_historials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visita_carcel_imputado_id');
            $table->foreign('visita_carcel_imputado_id', 'vcih_imputado_fk')->references('id')->on('visita_carcel_imputados');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivo');
            $table->json('cambios');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('penales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('penales')->insert(array_map(fn (string $nombre) => ['nombre' => $nombre, 'activo' => true, 'created_at' => now(), 'updated_at' => now()], ['CCP Nueva Imperial', 'CCP Valdivia', 'CCP Valparaiso', 'CCP Colina II', 'CP Biobio', 'CCP La Serena', 'CDP Puente Alto', 'CP Puerto Montt', 'CP Rancagua', 'CDP Stgo Uno', 'CDT Pudahuel Norte', 'REPAS', 'CDP Stgo Sur', 'CPF San Miguel', 'Cuartel Gral. PDI Borgoño', 'CP la Laguna Talca', 'CCP Talca']));
    }

    public function down(): void
    {
        Schema::dropIfExists('penales');
        Schema::dropIfExists('visita_carcel_imputado_historials');
        Schema::dropIfExists('visita_carcel_imputados');
        Schema::dropIfExists('visita_carcel_causas');
    }
};
