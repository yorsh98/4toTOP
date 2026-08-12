<?php

use App\Models\DocumentoMovimiento;
use App\Services\BackofficeDocumentosCatalog;

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
