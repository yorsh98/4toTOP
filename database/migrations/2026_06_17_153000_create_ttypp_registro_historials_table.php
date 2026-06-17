<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttypp_registro_historials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ttypp_registro_id')->constrained('ttypp_registros')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('cambios'); // Almacena campos cambiados, ej: {"modalidad": {"old": "Zoom", "new": "Presencial"}}
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttypp_registro_historials');
    }
};
