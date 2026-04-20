<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('event_key', 160);
            $table->string('idempotency_key', 80)->unique();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('queued');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['event_key']);
            $table->index(['status']);
            $table->index(['notification_type_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');
    }
};
