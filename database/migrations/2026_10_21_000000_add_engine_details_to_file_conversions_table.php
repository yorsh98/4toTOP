<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_conversions', function (Blueprint $table) {
            $table->string('engine', 30)->nullable()->after('status');
            $table->char('input_sha256', 64)->nullable()->after('input_path');
            $table->char('output_sha256', 64)->nullable()->after('output_path');
            $table->string('notice', 500)->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('file_conversions', function (Blueprint $table) {
            $table->dropColumn(['engine', 'input_sha256', 'output_sha256', 'notice']);
        });
    }
};
