<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'navbar.audios_salas.visible',
        'navbar.audios_salas.editable',
        'audios_salas.ver',
        'audios_salas.gestionar',
    ];

    public function up(): void
    {
        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        $now = now();

        DB::table($permissionsTable)->insertOrIgnore(
            array_map(fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], self::PERMISSIONS)
        );
    }

    public function down(): void
    {
        // Forward-only permission catalog.
    }
};
