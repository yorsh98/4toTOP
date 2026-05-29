<?php

use App\Livewire\TtyppPage;
use App\Models\TtyppRegistro;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin can create ttypp record with nullable fields', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('rit', '123-2026')
        ->set('ruc', null)
        ->set('tipo_audiencia', null)
        ->set('numero_dias', null)
        ->set('cantidad', null)
        ->set('modalidad', null)
        ->set('encargada_ti_mp', null)
        ->set('encargada_ti_d', null)
        ->set('encargada_zoom', null)
        ->set('acta', null)
        ->set('sala', null)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '123-2026',
        'ruc' => null,
    ]);
});

test('search filters ttypp list by ruc', function () {
    TtyppRegistro::query()->create([
        'rit' => '111-2026',
        'ruc' => 'RUC-UNO',
    ]);

    TtyppRegistro::query()->create([
        'rit' => '222-2026',
        'ruc' => 'RUC-DOS',
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('search', 'RUC-DOS')
        ->assertSee('222-2026')
        ->assertDontSee('111-2026');
});

test('consulta without ttypp importar permission cannot import csv', function () {
    $user = User::factory()->create();
    $user->assignRole('Consulta');

    $file = UploadedFile::fake()->createWithContent('ttypp.csv', "rit,ruc\n100-2026,RUC-100\n");

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importCsv', $file)
        ->call('importFromCsv')
        ->assertForbidden();
});

test('ttypp welcome module route is accessible', function () {
    $this->get(route('ttypp.welcome'))
        ->assertSuccessful()
        ->assertSee('TTyPP');
});
