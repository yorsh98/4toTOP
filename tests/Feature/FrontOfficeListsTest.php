<?php

use App\Livewire\BuscadorSentenciasTabla;
use App\Livewire\DocumentosBackofficePage;
use App\Models\AccesoDirecto;
use App\Models\buscadorsentencias;
use App\Models\Documento;
use App\Models\User;
use App\Services\BackofficeDocumentosCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

function documentoPublico(string $categoria, string $nombre, string $archivo, bool $publico = true): Documento
{
    return Documento::create([
        'categoria' => $categoria,
        'nombre' => $nombre,
        'archivo' => $archivo,
        'orden' => 1,
        'activo' => true,
        'publico' => $publico,
    ]);
}

test('documents page lists public documents with their format', function () {
    documentoPublico('documento', 'Ficha personal', 'documentos/doc/ficha.docx');
    documentoPublico('documento', 'Documento interno', 'documentos/doc/interno.pdf', publico: false);

    $this->get(route('doc'))
        ->assertSuccessful()
        ->assertSee('Ficha personal')
        ->assertSee('/storage/documentos/doc/ficha.docx', false)
        ->assertSee('Word')
        ->assertDontSee('Documento interno')
        ->assertSee('<b data-fo-filter-count>1</b>', false)
        ->assertSee('documento</span>', false);
});

test('procedures page shows an empty state that says where to publish', function () {
    $this->get(route('proc'))
        ->assertSuccessful()
        ->assertSee('Aún no hay procedimientos públicos')
        ->assertDontSee('data-fo-filter-bar', false);
});

test('unijud page groups courts by type and highlights the place', function () {
    $this->mock(BackofficeDocumentosCatalog::class, function ($mock) {
        $mock->shouldReceive('all')->andReturn(['meta' => ['updated_at' => '2026-10-01 09:30:00']]);
        $mock->shouldReceive('tribunales')->andReturn([
            ['nombre' => '10° Juzgado de Garantia de Santiago', 'activo' => true],
            ['nombre' => 'Juzgado de Garantia de Calama', 'activo' => true],
            ['nombre' => 'Juzgado de Letras y Garantia de Cabo de Hornos', 'activo' => true],
        ]);
    });

    $this->get(route('migraunijud'))
        ->assertSuccessful()
        ->assertSee('Lista actualizada el 01-10-2026 09:30')
        ->assertSeeInOrder(['Juzgado de Garantia', 'Calama', 'Santiago', '10°'])
        ->assertSee('Juzgado de Letras y Garantia')
        ->assertSee('Cabo de Hornos')
        ->assertSee('<b data-fo-filter-count>3</b>', false);
});

test('migration loads the thirty shortcuts that were hardcoded', function () {
    expect(AccesoDirecto::count())->toBe(30)
        ->and(AccesoDirecto::where('nombre', 'Gestión Penal 1')->value('url'))
        ->toBe('http://gestionpenal.reformaprocesal.pjud/webGestion/login.jsf');
});

test('shortcuts page shows only active links grouped and marks internal network ones', function () {
    AccesoDirecto::query()->delete();
    AccesoDirecto::create(['grupo' => 'Sistemas', 'nombre' => 'Gestión Penal', 'url' => 'http://gestionpenal.reformaprocesal.pjud/', 'orden' => 1]);
    AccesoDirecto::create(['grupo' => 'Sistemas', 'nombre' => 'Juris', 'url' => 'https://juris.pjud.cl/', 'orden' => 2]);
    AccesoDirecto::create(['grupo' => 'Nubes', 'nombre' => 'Enlace oculto', 'url' => 'https://example.org/', 'orden' => 3, 'activo' => false]);

    $html = $this->get(route('acc'))
        ->assertSuccessful()
        ->assertSee('Gestión Penal')
        ->assertSee('juris.pjud.cl')
        ->assertDontSee('Enlace oculto')
        ->assertSee('<b data-fo-filter-count>2</b>', false)
        ->getContent();

    expect(substr_count($html, 'fo-badge--net'))->toBe(1)
        ->and((new AccesoDirecto(['url' => 'https://juris.pjud.cl/']))->soloRedInterna())->toBeFalse();
});

test('sentencias search finds an exact RIT-year and filters by instance', function () {
    $base = ['ruc' => '2400123456-7', 'nombre_partes' => 'MP con Pérez', 'materia' => 'Robo', 'fecha_decision' => '2025-05-10', 'glosa_decision' => 'Condena', 'juez' => 'Juez A'];
    buscadorsentencias::create([...$base, 'rit' => 245, 'ano' => 2025, 'instancia' => 'Primera instancia']);
    buscadorsentencias::create([...$base, 'rit' => 245, 'ano' => 2024, 'instancia' => 'Corte Suprema']);

    Livewire::test(BuscadorSentenciasTabla::class)
        ->assertSeeHtml('<b class="fo-num">2</b>')
        ->set('search', '245-2025')
        ->assertSee('245-2025')
        ->assertDontSee('245-2024')
        ->set('search', '')
        ->set('filtroInstancia', 'Corte Suprema')
        ->assertSee('245-2024')
        ->assertDontSee('245-2025');
});

test('backoffice manages shortcuts and rejects non http urls', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->set('accesoGrupo', 'Sistemas')
        ->set('accesoNombre', 'Script')
        ->set('accesoUrl', 'javascript:alert(1)')
        ->call('saveAcceso')
        ->assertHasErrors('accesoUrl')
        ->set('accesoUrl', 'https://nuevo.pjud.cl/')
        ->set('accesoNombre', 'Nuevo sistema')
        ->call('saveAcceso')
        ->assertHasNoErrors();

    $nuevo = AccesoDirecto::where('nombre', 'Nuevo sistema')->firstOrFail();
    expect($nuevo->orden)->toBe(AccesoDirecto::max('orden'));

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->call('moveAcceso', $nuevo->id, -1)
        ->call('toggleAcceso', $nuevo->id);

    expect($nuevo->fresh())->orden->toBe(AccesoDirecto::max('orden') - 1)->activo->toBeFalse();
});

test('backoffice shortcut changes require the manage permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('documentos.ver');
    $acceso = AccesoDirecto::firstOrFail();

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->call('removeAcceso', $acceso->id)
        ->assertForbidden();

    expect(AccesoDirecto::find($acceso->id))->not->toBeNull();
});
