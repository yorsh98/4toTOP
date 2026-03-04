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
        Schema::create('buscadorsentencias', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 15)->nullable();
            $table->integer('rit')->nullable()->index();
            $table->integer('ano')->nullable()->index();
            $table->string('nombre_partes', 255)->nullable();
            $table->string('materia', 255)->nullable();
            $table->date('fecha_decision')->nullable()->index();
            $table->string('glosa_decision', 255)->nullable();
            $table->string('juez', 255)->nullable();
            $table->string('instancia', 128)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buscadorsentencias');
    }
};
