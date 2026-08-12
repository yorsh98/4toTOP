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
