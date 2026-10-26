<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_conversions', function (Blueprint $table) {
            // Merge: the input files in order. Split: the mode and its ranges.
            $table->json('options')->nullable()->after('input_path');
        });
    }

    public function down(): void
    {
        Schema::table('file_conversions', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
