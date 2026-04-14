<?php

use App\Livewire\Security\RolesPage;
use App\Livewire\Security\UsersRolesPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('creating a role stores security audit entry', function () {
    $superAdmin = User::factory()->create([
        'name' => 'Audit Admin',
        'email' => 'audit-admin@test.local',
    ]);
    $superAdmin->assignRole('SuperAdmin');

    $this->actingAs($superAdmin);

    Livewire::test(RolesPage::class)
        ->set('name', 'RoleAuditTest')
        ->call('createRole')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('security_audits', [
        'actor_user_id' => $superAdmin->id,
        'actor_email' => 'audit-admin@test.local',
        'action' => 'role.created',
        'entity_type' => 'role',
    ]);
});

test('updating user roles stores security audit entry', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('SuperAdmin');

    $targetUser = User::factory()->create([
        'email' => 'target-user@test.local',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(UsersRolesPage::class)
        ->set("selectedRoles.{$targetUser->id}", ['Operador'])
        ->call('saveUserRoles', $targetUser->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('security_audits', [
        'actor_user_id' => $superAdmin->id,
        'action' => 'user.roles.updated',
        'entity_type' => 'user',
        'entity_id' => (string) $targetUser->id,
    ]);
});
