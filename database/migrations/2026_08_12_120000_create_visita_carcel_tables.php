<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('visita_carcel_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');
            $table->foreignId('importado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('movimientos_creados')->default(0);
            $table->unsignedInteger('duplicados')->default(0);
            $table->unsignedInteger('causas_no_encontradas')->default(0);
            $table->unsignedInteger('imputados_no_encontrados')->default(0);
            $table->unsignedInteger('coincidencias_ambiguas')->default(0);
            $table->unsignedInteger('inconsistencias')->default(0);
            $table->timestamps();
        });

        Schema::create('visita_carcel_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visita_carcel_causa_id')->constrained('visita_carcel_causas');
            $table->foreignId('visita_carcel_imputado_id')->nullable()->constrained('visita_carcel_imputados')->nullOnDelete();
            $table->foreignId('visita_carcel_importacion_id')->constrained('visita_carcel_importaciones');
            $table->string('ruc');
            $table->string('rit');
            $table->string('tipo_tramite');
            $table->string('evento');
            $table->date('fecha_evento');
            $table->string('estado_evento');
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();
            $table->index(['visita_carcel_causa_id', 'fecha_evento'], 'vcm_causa_fecha_index');
            $table->index('visita_carcel_imputado_id', 'vcm_imputado_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visita_carcel_movimientos');
        Schema::dropIfExists('visita_carcel_importaciones');
        Schema::dropIfExists('visita_carcel_imputados');
        Schema::dropIfExists('visita_carcel_causas');
    }
};
