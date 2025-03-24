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
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 255);
            $table->string('rut', 12)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefono1', 15)->nullable();
            $table->string('telefono2', 15)->nullable();
            $table->string('institucion', 50)->nullable(); // Almacenará el valor del select
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
