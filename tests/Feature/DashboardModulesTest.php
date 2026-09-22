<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('dashboard only displays modules assigned to the user', function () {
    $permission = Permission::findOrCreate('navbar.turnos.visible', 'web');
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Turnos')
        ->assertDontSee('Funcionarios')
        ->assertDontSee('Audiencias');
});

test('dashboard displays insumos for users who can request supplies', function () {
    $permission = Permission::findOrCreate('insumos.solicitar', 'web');
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Insumos')
        ->assertSee(route('insumos.solicitudes'), false);
});

test('dashboard hides insumos from users without insumos permissions', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Solicita y consulta los insumos disponibles.')
        ->assertDontSee(route('insumos.solicitudes'), false);
});
