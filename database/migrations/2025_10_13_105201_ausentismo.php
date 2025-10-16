<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ausentismos', function (Blueprint $table) {
            $table->id();
            $table->string('funcionario_nombre', 150);
            $table->string('cargo', 120);
            $table->string('tipo_permiso');
            $table->date('fecha_inicio');
            $table->date('fecha_termino');
            $table->text('observacion')->nullable(); // tipo de permiso / motivo
            $table->timestamps();
            // Índices útiles
            $table->index(['fecha_inicio', 'fecha_termino']);
            $table->index('funcionario_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ausentismos');
    }
};
