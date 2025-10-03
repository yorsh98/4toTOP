<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audiencias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); // Filtro clave para monitores
            $table->string('rit'); // "123-2025"
            $table->string('sala');
            $table->string('ubicacion')->nullable();
            $table->time('hora_inicio');
            $table->string('ruc')->nullable();
            $table->string('cta_zoom')->nullable();
            $table->string('tipo_audiencia'); // Cambiado a string para texto libre
            $table->integer('num_testigos')->default(0)->nullable();
            $table->integer('num_peritos')->default(0)->nullable();
            $table->string('duracion')->nullable(); // Cambiado a string para "días" o "meses"
            $table->string('delito')->nullable();
            $table->json('jueces_inhabilitados')->nullable();
            $table->string('encargado_causa');
            $table->string('acta'); 
            $table->string('encargado_ttp')->nullable();
            $table->string('encargado_ttp_zoom')->nullable();
            $table->json('acusados'); // JSON estructurado con campos específicos
            $table->enum('estado', ['POR_REALIZARSE', 'EN_CURSO', 'RECESO', 'FINALIZADA'])->default('POR_REALIZARSE');
            $table->string('anfitrion')->nullable();    
            $table->string('JuezP')->nullable();
            $table->string('JuezR');
            $table->string('JuezI')->nullable();   
            $table->string('obs')->nullable();
            $table->timestamps();            
            $table->index(['fecha', 'estado']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audiencias');
    }
};
