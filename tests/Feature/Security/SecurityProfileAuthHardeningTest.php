<?php

use App\Livewire\Security\RolePermissionsPage;
use App\Livewire\Security\UsersRolesPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('locks the role and still saves legitimate permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);
    $permission = Permission::where('guard_name', 'web')->firstOrFail();
    $page = Livewire::actingAs($admin)->test(RolePermissionsPage::class, ['role' => $role]);

    expect(fn () => $page->set('role.name', 'SuperAdmin'))->toThrow(Exception::class, 'Cannot update locked property');
    $page->set('permissions', [$permission->name])->call('save')->assertHasNoErrors();
    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();
});

it('rejects permissions from another guard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);
    Permission::create(['name' => 'foreign.permission', 'guard_name' => 'api']);

    Livewire::actingAs($admin)->test(RolePermissionsPage::class, ['role' => $role])
        ->set('permissions', ['foreign.permission'])->call('save')->assertHasErrors(['permissions.0']);
});

it('still lets a security editor assign ordinary roles', function () {
    $editor = User::factory()->create();
    $editor->givePermissionTo('navbar.seguridad.editable');
    $target = User::factory()->create();
    $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);

    Livewire::actingAs($editor)->test(UsersRolesPage::class)
        ->set("selectedRoles.{$target->id}", [$role->name])->call('saveUserRoles', $target->id)->assertHasNoErrors();

    expect($target->fresh()->hasRole($role))->toBeTrue();
});

it('rejects roles that do not exist for the web guard', function () {
    $editor = User::factory()->create();
    $editor->givePermissionTo('navbar.seguridad.editable');
    $target = User::factory()->create();
    Role::create(['name' => 'ApiRole', 'guard_name' => 'api']);

    Livewire::actingAs($editor)->test(UsersRolesPage::class)
        ->set("selectedRoles.{$target->id}", ['ApiRole'])->call('saveUserRoles', $target->id)
        ->assertHasErrors();
});

it('protects the last active SuperAdmin from rejection and suspension', function () {
    $editor = User::factory()->create();
    $editor->givePermissionTo('navbar.seguridad.editable');
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $disabled = User::factory()->create(['suspended_at' => now()]);
    $disabled->assignRole('SuperAdmin');

    Livewire::actingAs($editor)->test(UsersRolesPage::class)->call('rejectUser', $admin->id);
    Livewire::actingAs($editor)->test(UsersRolesPage::class)->call('setUserSuspension', $admin->id, true);

    expect($admin->fresh()->rejected_at)->toBeNull()
        ->and($admin->fresh()->suspended_at)->toBeNull();
});

it('still allows rejecting or suspending a SuperAdmin when another one stays active', function () {
    $editor = User::factory()->create();
    $editor->givePermissionTo('navbar.seguridad.editable');
    $keeps = User::factory()->create();
    $keeps->assignRole('SuperAdmin');
    $other = User::factory()->create();
    $other->assignRole('SuperAdmin');
    $already = User::factory()->create(['suspended_at' => now()]);
    $already->assignRole('SuperAdmin');

    Livewire::actingAs($editor)->test(UsersRolesPage::class)->call('setUserSuspension', $other->id, true);
    Livewire::actingAs($editor)->test(UsersRolesPage::class)->call('rejectUser', $already->id);

    expect($other->fresh()->suspended_at)->not->toBeNull()
        ->and($already->fresh()->rejected_at)->not->toBeNull();
});
