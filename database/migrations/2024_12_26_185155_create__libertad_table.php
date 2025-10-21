<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libertad', function (Blueprint $table) {
            $table->id();
            $table->integer('Numentregado');
            $table->year('año');
            $table->string('CausaAsig', 10);
            $table->string('UserSolicitante');
            $table->string('UserDirigido');
            $table->timestamps();

            // Soft deletes
            $table->softDeletes(); // 'deleted_at' (nullable)
            // $table->softDeletesTz(); // si trabajas con TZ

            // Columna generada para “unicidad solo en activos”
            $table->boolean('deleted_at_is_null')->virtualAs('IF(deleted_at IS NULL, 1, 0)');

            // Índice único compuesto: solo bloqueará duplicados cuando deleted_at sea NULL
            $table->unique(['Numentregado', 'año', 'deleted_at_is_null'], 'uniq_libertad_num_anio_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libertad');
    }
};
