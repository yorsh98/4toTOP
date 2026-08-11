<?php

use App\Livewire\AudiosSalasPage;
use App\Models\AudioSalaEnlace;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['navbar.audios_salas.visible', 'navbar.audios_salas.editable', 'audios_salas.ver', 'audios_salas.gestionar'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

test('user with audio view permission can consult current and historical links', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('audios_salas.ver');

    AudioSalaEnlace::query()->create(['anio' => 2025, 'sala' => '701', 'url' => 'https://audios.test/2025', 'created_by' => $user->id, 'updated_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(AudiosSalasPage::class)
        ->assertSee('Sala 701')
        ->set('anio', '2025')
        ->assertSee('https://audios.test/2025');
});

test('user without audio view permission cannot access module', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('audios-salas.index'))
        ->assertForbidden();
});

test('only users with management permission can update links', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('audios_salas.ver');

    Livewire::actingAs($viewer)
        ->test(AudiosSalasPage::class)
        ->call('guardar')
        ->assertForbidden();

    $manager = User::factory()->create();
    $manager->givePermissionTo(['audios_salas.ver', 'audios_salas.gestionar']);

    Livewire::actingAs($manager)
        ->test(AudiosSalasPage::class)
        ->set('anio', '2026')
        ->set('enlaces.701', 'https://audios.test/2026/sala-701')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('audio_sala_enlaces', [
        'anio' => 2026,
        'sala' => '701',
        'url' => 'https://audios.test/2026/sala-701',
        'updated_by' => $manager->id,
    ]);
});

test('audio links require an http or https url', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['audios_salas.ver', 'audios_salas.gestionar']);

    Livewire::actingAs($user)
        ->test(AudiosSalasPage::class)
        ->set('enlaces.701', 'ftp://audios.test/sala-701')
        ->call('guardar')
        ->assertHasErrors(['enlaces.701']);
});

test('public web audios entry redirects to authenticated module', function () {
    $this->get('/')
        ->assertSee(route('audios-salas.index', [], false));
});
