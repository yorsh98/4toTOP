<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DocumentosCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (['procedimientos' => 'procedimiento', 'documentos' => 'documento'] as $section => $categoria) {
            foreach (config("backoffice_documentos.{$section}", []) as $index => $row) {
                $nombre = trim((string) Arr::get($row, 'nombre'));
                $archivo = ltrim((string) Arr::get($row, 'url'), '/');
                $archivo = preg_replace('#^storage/#', '', $archivo) ?? $archivo;

                if ($nombre === '' || $archivo === '') {
                    continue;
                }

                $attributes = ['categoria' => $categoria, 'nombre' => $nombre];
                $values = [
                    'archivo' => $archivo,
                    'orden' => max(1, (int) Arr::get($row, 'orden', $index + 1)),
                    'activo' => (bool) Arr::get($row, 'activo', true),
                    'publico' => (bool) Arr::get($row, 'publico', true),
                    'updated_at' => $now,
                ];

                if (! DB::table('documentos')->where($attributes)->exists()) {
                    $values['created_at'] = $now;
                }

                DB::table('documentos')->updateOrInsert($attributes, $values);
            }
        }
    }
}
