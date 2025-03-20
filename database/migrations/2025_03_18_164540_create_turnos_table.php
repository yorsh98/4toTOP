<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTurnosTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up()
{
    Schema::create('turno', function (Blueprint $table) {
        $table->id();
        
        // Campos para magistrados, funcionarios y jefaturas (Turno 1)
        $table->string('TM1')->nullable();
        $table->string('TM2')->nullable();
        $table->string('TM3')->nullable();
        $table->string('TF1')->nullable();
        $table->string('TF2')->nullable();
        $table->string('TF3')->nullable();
        $table->string('TJ1')->nullable();
        $table->string('TJ2')->nullable();
        $table->string('TJ3')->nullable();
        
        // Campos para magistrados, funcionarios y jefaturas ACD (Turno 2)
        $table->string('ACDM1')->nullable();
        $table->string('ACDM2')->nullable();
        $table->string('ACDM3')->nullable();
        $table->string('ACDF1')->nullable();
        $table->string('ACDF2')->nullable();
        $table->string('ACDF3')->nullable();
        $table->string('ACDJ1')->nullable();
        $table->string('ACDJ2')->nullable();
        $table->string('ACDJ3')->nullable();
        
        $table->string('FECHA')->nullable();
        $table->timestamps();
    });
}

    /**
     * Revierte las migraciones.
     */
    public function down()
    {
        Schema::dropIfExists('turno');
    }
}
