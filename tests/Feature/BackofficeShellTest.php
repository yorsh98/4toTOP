<?php

use App\Models\User;
use App\Services\BackofficeModules;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

test('backoffice shell includes its theme and body class', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('class="bo antialiased"', false)
        ->assertSee("localStorage.getItem('fo-theme')", false)
        ->assertSee('document.documentElement.dataset.theme', false);
});

test('backoffice catalog contains sixteen existing routes', function () {
    $modules = app(BackofficeModules::class)->items();

    expect($modules)->toHaveCount(16);
    foreach ($modules as $module) {
        expect(Route::has($module['route']))->toBeTrue();
    }
});

test('dashboard hides modules that are not assigned to the user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('navbar.turnos.visible', 'web'));

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Consulta y asigna turnos semanales.')
        ->assertDontSee('Administra el catálogo de funcionarios.')
        ->assertDontSee('Organiza la programación y gestión diaria.');
});
