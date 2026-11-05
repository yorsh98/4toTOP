<?php

use App\Livewire\AudienciaForm;
use App\Livewire\AudienciasCortasBatch;
use App\Livewire\AudienciasDiarias;
use App\Livewire\AudienciasDiariasCorreo;
use App\Livewire\CambiarEstadoAudiencia;
use App\Livewire\LecturasSentenciaBatch;
use App\Models\Audiencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('SuperAdmin');
});

it('protects server managed hearing and mail state', function (string $component, string $property, mixed $value, array $params) {
    $livewire = Livewire::actingAs($this->user)->test($component, $params);
    expect(fn () => $livewire->set($property, $value))->toThrow(Exception::class, 'Cannot update locked property');
})->with([
    [AudienciaForm::class, 'acusados', [], []],
    [AudienciaForm::class, 'jueces_inhabilitados', [], []],
    [AudienciasCortasBatch::class, 'editId', 1, []],
    [LecturasSentenciaBatch::class, 'editId', 1, []],
    [AudienciasDiarias::class, 'editId', 1, []],
    [AudienciasDiariasCorreo::class, 'firmaPreview', '<script>alert(1)</script>', ['fecha' => '2026-08-06']],
    [AudienciasDiariasCorreo::class, 'recipients', ['attacker@example.com'], ['fecha' => '2026-08-06']],
    [AudienciasDiariasCorreo::class, 'envioDispatchId', 1, ['fecha' => '2026-08-06']],
]);

it('rejects unknown hearing types and states', function () {
    Livewire::actingAs($this->user)->test(AudienciaForm::class)
        ->set('tipo_audiencia', 'Otro')
        ->set('estado', 'Otro')
        ->call('guardarAudiencia')
        ->assertHasErrors(['tipo_audiencia', 'estado']);
});

it('limits lectura batches and ignores invalid daily dates and filters', function () {
    $component = Livewire::actingAs($this->user)->test(LecturasSentenciaBatch::class);
    $row = $component->get('items')[0];
    $component->set('items', array_fill(0, 6, $row))->call('guardar')->assertHasErrors(['items']);

    Livewire::actingAs($this->user)->test(AudienciasDiarias::class)
        ->set('fecha', '2026-99-99')->assertSet('fecha', now()->toDateString())
        ->call('closeEditAfterSave', 'invalid')->assertSet('fecha', now()->toDateString());

    Livewire::test(CambiarEstadoAudiencia::class)
        ->call('setFiltro', 'invalido')->assertSet('filtro', null)
        ->call('setFiltro', 'Juicio Oral')->assertSet('filtro', 'Juicio Oral');
});

it('keeps the public export and preview available and tolerates an invalid date', function () {
    $this->get(route('audiencias.export.diaria', ['fecha' => '2026-08-06']))
        ->assertDownload('Programacion_4TOP_2026-08-06.xlsx');
    $this->get(route('preview.progra', ['fecha' => 'invalid']))->assertSuccessful();
    $this->get('/progra?fecha=invalid')->assertSuccessful();
});

it('saves a lectura with its fixed type even when the row is manipulated', function () {
    Livewire::actingAs($this->user)->test(LecturasSentenciaBatch::class)
        ->set('items.0.fecha', '2026-05-20')
        ->set('items.0.hora_inicio', '11:00')
        ->set('items.0.JuezR', 'Juez')
        ->set('items.0.tipo_audiencia', 'Otro')
        ->set('items.0.sala', 'Sala 2')
        ->set('items.0.rit', '200-2026')
        ->set('items.0.ruc', '22222222-2')
        ->set('items.0.encargado_sala', 'Encargado')
        ->set('items.0.acta', 'Acta')
        ->set('items.0.acusados', [['nombre_completo' => 'Persona', 'situacion' => 'Libre', 'medida_cautelar' => '', 'forma_notificacion' => '']])
        ->call('guardar')->assertHasNoErrors();

    $this->assertDatabaseHas('audiencias', ['rit' => '200-2026', 'tipo_audiencia' => 'Lectura de Sentencia']);
});

it('only changes today hearings to known states', function () {
    $data = ['ruc' => '123', 'sala' => 'Sala 1', 'hora_inicio' => '10:00', 'tipo_audiencia' => 'Juicio Oral', 'encargado_causa' => 'Encargado', 'acta' => 'Acta', 'JuezR' => 'Juez', 'acusados' => [], 'estado' => 'POR_REALIZARSE'];
    $today = Audiencia::create($data + ['fecha' => today(), 'rit' => '1']);
    $past = Audiencia::create($data + ['fecha' => today()->subDay(), 'rit' => '2']);
    $component = Livewire::actingAs($this->user)->test(CambiarEstadoAudiencia::class);
    $component->call('cambiarEstado', $today->id, 'OTRO');
    expect($today->fresh()->estado)->toBe('POR_REALIZARSE');
    $component->call('cambiarEstado', $today->id, 'EN_CURSO');
    expect($today->fresh()->estado)->toBe('EN_CURSO');
    expect(fn () => $component->call('cambiarEstado', $past->id, 'EN_CURSO'))->toThrow(ModelNotFoundException::class);
    expect($past->fresh()->estado)->toBe('POR_REALIZARSE');
});
