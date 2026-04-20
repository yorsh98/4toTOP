<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_pruebas_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bitacora_pruebas_dia_id')
                ->constrained('bitacora_pruebas_dias')
                ->cascadeOnDelete();
            $table->string('nombre_prueba');
            $table->string('institucion_exhibe');
            $table->foreignId('ingresado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('bitacora_pruebas_dia_id');
            $table->index('ingresado_por_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_pruebas_items');
    }
};
