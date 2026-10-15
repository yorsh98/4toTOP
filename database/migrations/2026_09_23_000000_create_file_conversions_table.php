<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('area', 20);
            $table->string('format', 10);
            $table->string('status', 20)->default('queued');
            $table->string('original_name');
            $table->string('result_name');
            $table->string('input_path');
            $table->string('output_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('ip_address', 45);
            $table->string('session_hash', 64)->index();
            $table->text('user_agent')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('files_deleted_at')->nullable();
            $table->timestamps();
            $table->index(['session_hash', 'format', 'status']);
            $table->index(['created_at', 'area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_conversions');
    }
};
