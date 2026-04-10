<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('email_verified_at')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at')->index();
            $table->string('approval_note', 255)->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('approval_note')->index();
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at')->index();
            $table->string('rejection_note', 255)->nullable()->after('rejected_by');
        });

        DB::table('users')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => now(),
                'approval_note' => 'Aprobación automática por migración de control de acceso',
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'approved_by',
                'approval_note',
                'rejected_at',
                'rejected_by',
                'rejection_note',
            ]);
        });
    }
};
