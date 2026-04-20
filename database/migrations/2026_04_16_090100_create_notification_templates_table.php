<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('subject', 200);
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('variables_schema')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_type_id', 'version']);
            $table->index(['notification_type_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
