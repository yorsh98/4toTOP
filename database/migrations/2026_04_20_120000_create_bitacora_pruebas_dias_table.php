<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_pruebas_dias', function (Blueprint $table) {
            $table->id();
            $table->string('rit');
            $table->string('enlace_acta')->nullable();
            $table->unsignedInteger('numero_dia');
            $table->date('fecha_dia');
            $table->timestamps();
            $table->softDeletes();

            $table->index('rit');
            $table->index(['rit', 'numero_dia']);
            $table->index('fecha_dia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_pruebas_dias');
    }
};
