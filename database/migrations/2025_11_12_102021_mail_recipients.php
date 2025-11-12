<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nombre')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('mail_recipients');
    }
};
