<?php

use App\Livewire\AudiosSalasPage;
use App\Livewire\Ausentismo;
use App\Livewire\BitacoraPruebasPage;
use App\Livewire\DocumentosBackofficePage;
use App\Livewire\FuncionariosPage;
use App\Livewire\ServiciosInventarioPage;
use App\Livewire\TtyppPage;
use App\Livewire\VisitaCarcelPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('SuperAdmin');
});

it('rejects client updates to server-owned state', function (string $component, string $property, mixed $value) {
    $instance = Livewire::actingAs($this->user)->test($component);
    expect(fn () => $instance->set($property, $value))->toThrow(Exception::class, 'Cannot update locked property');
})->with([
    [DocumentosBackofficePage::class, 'editingAccesoId', 1],
    [DocumentosBackofficePage::class, 'sectionsOpen', []],
    [TtyppPage::class, 'editingId', 1],
    [TtyppPage::class, 'selectedRegistroHistorial', []],
    [VisitaCarcelPage::class, 'modo', 'reportes'],
    [VisitaCarcelPage::class, 'imputadoRevisionPpId', 1],
    [VisitaCarcelPage::class, 'imputadoEditandoId', 1],
    [VisitaCarcelPage::class, 'causaSeleccionadaId', 1],
    [VisitaCarcelPage::class, 'causasPendientes', []],
    [VisitaCarcelPage::class, 'imputadosPendientes', []],
    [VisitaCarcelPage::class, 'coincidenciasCruce', []],
    [ServiciosInventarioPage::class, 'editingId', 1],
    [FuncionariosPage::class, 'editingId', 1],
    [Ausentismo::class, 'editingId', 1],
    [Ausentismo::class, 'tipos_ausencia', []],
    [BitacoraPruebasPage::class, 'showViewer', true],
    [BitacoraPruebasPage::class, 'showNotificationForm', true],
    [AudiosSalasPage::class, 'configuracionAbierta', true],
]);

it('keeps authorized state transitions available', function () {
    Livewire::actingAs($this->user)->test(VisitaCarcelPage::class)
        ->call('seleccionarModo', 'reportes')->assertSet('modo', 'reportes');
    Livewire::actingAs($this->user)->test(ServiciosInventarioPage::class)
        ->call('selectPiso', 7)->assertSet('piso', 7);
    Livewire::actingAs($this->user)->test(TtyppPage::class)
        ->set('rit', '123-2026')->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('ttypp_registros', ['rit' => '123-2026']);
});

it('handles invalid dates and preserves valid date navigation', function () {
    Livewire::actingAs($this->user)->test(TtyppPage::class)
        ->set('selectedDate', 'no-es-fecha')->call('nextDay')->assertHasNoErrors()
        ->set('selectedDate', '2026-06-10')->call('nextDay')->assertSet('selectedDate', '2026-06-11');
    Livewire::actingAs($this->user)->test(VisitaCarcelPage::class)
        ->call('seleccionarModo', 'reportes')->set('reporteDesde', 'invalida')
        ->set('reporteHasta', 'invalida')->assertSuccessful();
});

it('bounds pagination and rejects invalid absence types', function () {
    Livewire::actingAs($this->user)->test(Ausentismo::class)
        ->set('perPage', 1000000)->assertSuccessful()
        ->set('funcionario_nombre', 'Persona de prueba')->set('cargo', 'Juez/a')
        ->set('tipo_permiso', 'inventado')->set('fecha_inicio', '2026-06-10')
        ->set('fecha_termino', '2026-06-11')->call('create')->assertHasErrors(['tipo_permiso'])
        ->set('tipo_permiso', 'Feriado Legal')->call('create')->assertHasNoErrors();
});

it('keeps editing an absence whose stored type predates the current catalog', function () {
    $ausencia = App\Models\Ausentismo::create([
        'funcionario_nombre' => 'Persona Antigua',
        'cargo' => 'Juez/a',
        'tipo_permiso' => 'licencia medica',
        'fecha_inicio' => '2026-06-10',
        'fecha_termino' => '2026-06-11',
        'observacion' => null,
    ]);

    Livewire::actingAs($this->user)->test(Ausentismo::class)
        ->call('edit', $ausencia->id)
        ->set('observacion', 'Control')
        ->call('update')
        ->assertHasNoErrors();

    expect($ausencia->fresh()->observacion)->toBe('Control');
});
