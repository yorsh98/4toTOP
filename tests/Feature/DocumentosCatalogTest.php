<?php

use App\Livewire\DocumentosBackofficePage;
use App\Models\DocumentoMovimiento;
use App\Models\User;
use App\Services\BackofficeDocumentosCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('stores documents in the database and archives them with a trace', function () {
    $catalog = app(BackofficeDocumentosCatalog::class);
    $before = count($catalog->documentos());

    $documento = $catalog->addDocumento('documento', 'Formulario de prueba', 'documentos/doc/prueba.pdf', 1);

    expect($catalog->documentos())->toHaveCount($before + 1)
        ->and(DocumentoMovimiento::where('documento_id', $documento->id)->value('accion'))->toBe('creado');

    $catalog->archiveDocumento($documento->id);

    expect($catalog->documentos())->toHaveCount($before)
        ->and(DocumentoMovimiento::where('documento_id', $documento->id)->latest('id')->value('accion'))->toBe('archivado');
});

it('dispatches a toast after adding a document', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->set('documentoNombre', 'Documento de prueba')
        ->set('documentoOrden', 1)
        ->set('documentoArchivo', UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'))
        ->call('addDocumento')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');
});
