<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activos', function (Blueprint $table) {
            $table->string('origen_importacion')->nullable()->unique()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('activos', function (Blueprint $table) {
            $table->dropUnique(['origen_importacion']);
            $table->dropColumn('origen_importacion');
        });
    }
};
