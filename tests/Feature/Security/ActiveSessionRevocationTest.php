<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('suspended user with an open session is logged out on protected routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->forceFill(['suspended_at' => now()])->save();

    $this->get('/profile')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('rejected user with an open session is logged out on protected routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->forceFill(['approved_at' => null, 'rejected_at' => now()])->save();

    $this->get('/profile')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('unapproved user with an open session is logged out on protected routes', function () {
    $user = User::factory()->create(['approved_at' => null]);
    $this->actingAs($user);

    $this->get('/profile')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('livewire requests from a suspended user are rejected with 401', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->actingAs($user)
        ->withHeaders(['X-Livewire' => 'true'])
        ->post('/livewire/update', [])
        ->assertUnauthorized();

    $this->assertGuest();
});

test('suspended user with a leftover session still gets public pages as guest', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->actingAs($user)->get('/')->assertOk();

    $this->assertGuest();
});

test('active user is not affected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('revokeAccess rotates the remember token and deletes stored sessions', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create(['remember_token' => 'token-original']);
    $other = User::factory()->create();

    foreach ([[$user, 's1'], [$user, 's2'], [$other, 's3']] as [$owner, $id]) {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $owner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }

    $user->revokeAccess();

    expect($user->fresh()->remember_token)->not->toBe('token-original')
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);
});
