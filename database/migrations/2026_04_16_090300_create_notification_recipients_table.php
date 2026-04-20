<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('notification_dispatches')->cascadeOnDelete();
            $table->string('type', 10)->default('to');
            $table->string('name', 180)->nullable();
            $table->string('email', 190);
            $table->string('context', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('provider_message_id', 180)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['dispatch_id', 'status']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
