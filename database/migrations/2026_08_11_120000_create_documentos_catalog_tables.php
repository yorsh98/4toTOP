<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 30)->index();
            $table->string('nombre');
            $table->string('archivo');
            $table->unsignedInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->boolean('publico')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['categoria', 'activo', 'publico', 'orden']);
        });

        Schema::create('documento_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 30);
            $table->json('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $catalogPath = storage_path('app/backoffice/documentos_catalogo.json');
        $catalog = is_file($catalogPath) ? json_decode((string) file_get_contents($catalogPath), true) : null;
        $catalog = is_array($catalog) ? $catalog : config('backoffice_documentos', []);
        $now = now();

        foreach (['procedimientos' => 'procedimiento', 'documentos' => 'documento'] as $section => $categoria) {
            foreach (Arr::get($catalog, $section, []) as $index => $row) {
                $nombre = trim((string) Arr::get($row, 'nombre'));
                $archivo = ltrim((string) Arr::get($row, 'url'), '/');
                $archivo = preg_replace('#^storage/#', '', $archivo) ?? $archivo;

                if ($nombre === '' || $archivo === '') {
                    continue;
                }

                DB::table('documentos')->insert([
                    'categoria' => $categoria,
                    'nombre' => $nombre,
                    'archivo' => $archivo,
                    'orden' => max(1, (int) Arr::get($row, 'orden', $index + 1)),
                    'activo' => (bool) Arr::get($row, 'activo', true),
                    'publico' => (bool) Arr::get($row, 'publico', true),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_movimientos');
        Schema::dropIfExists('documentos');
    }
};
