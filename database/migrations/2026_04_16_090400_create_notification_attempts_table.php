<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('notification_dispatches')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('channel', 40)->default('mail');
            $table->string('status', 20)->default('processing');
            $table->string('error_class', 180)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['dispatch_id', 'attempt_number']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_attempts');
    }
};
