<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
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
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    $response->assertRedirect(route('dashboard'));
});

test('a registered user joins planta lima as an operator', function () {
    $plant = Team::factory()->create([
        'name' => 'Planta Lima',
        'slug' => 'planta-lima',
        'is_personal' => false,
    ]);

    $this->post(route('register.store'), [
        'name' => 'Camille Meadows',
        'email' => 'givo@mailinator.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'givo@mailinator.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->current_team_id)->toBe($plant->id);

    $membership = $plant->memberships()->where('user_id', $user?->id)->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(TeamRole::Member)
        ->and($membership->grantedSections())->toBe(['oee']);
});
