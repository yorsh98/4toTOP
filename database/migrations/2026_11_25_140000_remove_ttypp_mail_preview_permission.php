<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NAME = 'ttypp.mail.preview';

    public function up(): void
    {
        Permission::query()->where('name', self::NAME)->get()->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::findOrCreate(self::NAME, 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
