// database/migrations/2025_11_12_000000_create_mail_signatures_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mail_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');          // etiqueta visible en el selector
            $table->longText('html');          // html del pie de firma
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(1);
            $table->string('from_env')->nullable(); // p.ej. MAIL_FROM_LUIS
            $table->string('mailer')->nullable();   // p.ej. 'luis', 'eduardo', 'amelia_admin', ...
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('mail_signatures');
    }
};
