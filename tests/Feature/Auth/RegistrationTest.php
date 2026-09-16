<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('allowedEmailDomain', 'ecaral.pe'));
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
    );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@ecaral.pe',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@ecaral.pe')->first();
    $response->assertRedirect(route('dashboard'));
});

test('registration accepts company emails regardless of case', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'persona@ECARAL.PE',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated();
    expect(strtolower((string) auth()->user()?->email))->toBe('persona@ecaral.pe');
});

test('registration rejects emails outside the company domain', function (string $email) {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->where('email', $email)->exists())->toBeFalse();
})->with([
    'gmail' => 'test@gmail.com',
    'example' => 'test@example.com',
    'lookalike domain' => 'test@notecaral.pe',
]);

test('inertia registration redirects stay on the forwarded https host', function () {
    $response = $this
        ->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'sirvo.test',
            'X-Inertia' => 'true',
            'Accept' => 'text/html, application/xhtml+xml',
        ])
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'inertia@ecaral.pe',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://sirvo.test/');
});

test('a registered user joins planta lima as an operator', function () {
    $plant = Team::factory()->create([
        'name' => 'Planta Lima',
        'slug' => 'planta-lima',
        'is_personal' => false,
    ]);

    $this->post(route('register.store'), [
        'name' => 'Camille Meadows',
        'email' => 'givo@ecaral.pe',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'givo@ecaral.pe')->first();

    expect($user)->not->toBeNull()
        ->and($user->current_team_id)->toBe($plant->id);

    $membership = $plant->memberships()->where('user_id', $user?->id)->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(TeamRole::Member)
        ->and($membership->grantedSections())->toBe(['oee']);
});
