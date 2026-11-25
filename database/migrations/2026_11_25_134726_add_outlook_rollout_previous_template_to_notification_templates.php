<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('outlook_rollout_previous_template_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->dropColumn('outlook_rollout_previous_template_id');
        });
    }
};
