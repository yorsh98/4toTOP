<?php

use App\Models\User;
use Database\Seeders\LocalTestUserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('the test account cannot be seeded outside local even with a password configured', function (string $environment) {
    app()->instance('env', $environment);
    config(['security.local_test_user_password' => Str::random(32)]);

    expect(fn () => $this->artisan('db:seed', [
        '--class' => LocalTestUserSeeder::class,
        '--force' => true,
    ])->run())->toThrow(RuntimeException::class, 'solo se puede crear en el entorno local');

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
})->with(['production', 'staging', 'testing']);

test('the local seeder requires an explicit password', function () {
    app()->instance('env', 'local');
    config(['security.local_test_user_password' => null]);

    expect(fn () => $this->seed(LocalTestUserSeeder::class))
        ->toThrow(RuntimeException::class, 'LOCAL_TEST_USER_PASSWORD');

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});

test('the local seeder creates an approved user without resetting it on rerun', function () {
    app()->instance('env', 'local');
    $password = Str::random(32);
    config(['security.local_test_user_password' => $password]);

    $this->seed(LocalTestUserSeeder::class);
    config(['security.local_test_user_password' => Str::random(32)]);
    $this->seed(LocalTestUserSeeder::class);

    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($user->isApproved())->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check($password, $user->password))->toBeTrue();
});
