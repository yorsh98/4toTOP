<?php

use App\Livewire\InsumosGestionPage;
use App\Models\Funcionario;
use App\Models\InsumoSolicitud;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('direct assignment lists funcionarios from the catalog', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.gestionar');
    Funcionario::create([
        'nombre' => 'Funcionario del catalogo', 'rol' => 'informatico', 'email' => 'catalogo@example.cl', 'fecha_cumpleanos' => '1990-01-01',
    ]);

    Livewire::actingAs($user)
        ->test(InsumosGestionPage::class)
        ->assertSee('Funcionario del catalogo')
        ->assertDontSeeHtml('wire:model.number="directSectorId"');
});

test('management queue excludes partially delivered requests', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.gestionar');
    $requester = User::factory()->create();
    $pending = InsumoSolicitud::create(['solicitante_user_id' => $requester->id, 'estado' => 'pendiente']);
    $partial = InsumoSolicitud::create(['solicitante_user_id' => $requester->id, 'estado' => 'parcial']);

    Livewire::actingAs($user)
        ->test(InsumosGestionPage::class)
        ->assertSee("Solicitud #{$pending->id}")
        ->assertDontSee("Solicitud #{$partial->id}");
});
