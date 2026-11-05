<?php

use App\Livewire\DocumentosBackofficePage;
use App\Livewire\TablaGuias;
use App\Models\Guias;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- #1 Subida de documentos: la extensión no puede venir del cliente ----------------

it('rejects an html file disguised as a pdf in the documentos upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $polyglot = UploadedFile::fake()->createWithContent('instructivo.html', "%PDF-1.4\n<script>alert(1)</script>");

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->set('documentoNombre', 'Documento falso')
        ->set('documentoOrden', 1)
        ->set('documentoArchivo', $polyglot)
        ->call('addDocumento')
        ->assertHasErrors(['documentoArchivo']);

    expect(Storage::disk('public')->allFiles('documentos'))->toBeEmpty();
});

it('stores a legitimate pdf with a pdf extension', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(DocumentosBackofficePage::class)
        ->set('documentoNombre', 'Documento válido')
        ->set('documentoOrden', 1)
        ->set('documentoArchivo', UploadedFile::fake()->create('formulario.PDF', 20, 'application/pdf'))
        ->call('addDocumento')
        ->assertHasNoErrors();

    $files = Storage::disk('public')->allFiles('documentos');

    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.pdf');
});

// --- #2 y #6 TablaGuias: modo y orden no los controla el cliente -----------------------

it('does not let a guest leave lectura mode or sort by rut in the public directory', function () {
    Guias::query()->create([
        'nombre_completo' => 'Persona Publica',
        'rut' => '11111111-1',
        'email' => 'persona@example.test',
        'telefono1' => '111',
        'telefono2' => '222',
        'institucion' => 1,
    ]);

    $component = Livewire::test(TablaGuias::class, ['modo' => 'lectura']);

    expect(fn () => $component->set('modo', 'x'))->toThrow(Exception::class, 'Cannot update locked property');
    expect(fn () => $component->set('sortField', 'rut'))->toThrow(Exception::class, 'Cannot update locked property');

    $component->call('sortBy', 'rut')->assertSet('sortField', 'nombre_completo');
    $component->assertDontSee('11111111-1');
});

it('keeps sorting by allowed columns in the public directory', function () {
    $component = Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->call('sortBy', 'email');

    $component->assertSet('sortField', 'email')->assertSet('sortDirection', 'asc');
});

it('forbids a guest from rendering the backoffice guias table', function () {
    Livewire::test(TablaGuias::class, ['modo' => 'full'])->assertForbidden();
});

// --- #3 Sesiones tras cambio o restablecimiento de contraseña -------------------------

function insertSessionFor(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => time(),
    ]);
}

it('revokes other sessions after a password reset', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create();
    insertSessionFor($user, 'sesion-vieja');
    $token = Password::createToken($user);

    Volt::test('pages.auth.reset-password', ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'NuevaClave-2026!')
        ->set('password_confirmation', 'NuevaClave-2026!')
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('revokes other sessions but keeps the current one after changing the password', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create();
    insertSessionFor($user, 'otro-dispositivo');

    $this->actingAs($user);
    $currentId = session()->getId();
    insertSessionFor($user, $currentId);

    Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', 'NuevaClave-2026!')
        ->set('password_confirmation', 'NuevaClave-2026!')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $ids = DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all();

    expect($ids)->toBe([$currentId]);
});

// --- #4 Borrado de la propia cuenta ---------------------------------------------------

it('refuses to delete the last SuperAdmin account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    User::factory()->create();

    $this->actingAs($admin);

    Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('refuses to delete the only user in the system', function () {
    $solo = User::factory()->create();

    $this->actingAs($solo);

    Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect(User::query()->count())->toBe(1);
});

it('still lets a regular user delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});
