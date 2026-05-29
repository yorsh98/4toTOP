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
            $table->string('rit')->nullable();
            $table->string('ruc')->nullable();
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
            $table->date('fecha_asignacion')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('rit');
            $table->index('ruc');
            $table->index('fecha_asignacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttypp_registros');
    }
};
