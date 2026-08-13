<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttypp_registros', function (Blueprint $table) {
            $table->id();
            $table->string('rit')->nullable()->index();
            $table->string('ruc')->nullable()->index();
            $table->string('tipo_audiencia')->nullable();
            $table->string('numero_dias')->nullable();
            $table->string('cantidad')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('encargada_ti_mp')->nullable();
            $table->string('encargada_ti_d')->nullable();
            $table->string('encargada_zoom')->nullable();
            $table->string('acta')->nullable();
            $table->string('sala')->nullable();
            $table->string('jueces')->nullable();
            $table->date('fecha_asignacion')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ttypp_registro_historials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ttypp_registro_id')->constrained('ttypp_registros')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('cambios');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttypp_registro_historials');
        Schema::dropIfExists('ttypp_registros');
    }
};
