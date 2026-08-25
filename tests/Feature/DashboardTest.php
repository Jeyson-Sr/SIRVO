<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('users without dashboard access are sent to the oee panel when they may open it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Oee->value],
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertRedirect(route('oee.dashboard', $team));
});

test('users without dashboard access are sent to turnos when that is their first section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Productions->value],
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertRedirect(route('oee.productions.index', $team));
});

test('users without dashboard access are sent to stop codes when that is their first section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Catalog->value],
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertRedirect(route('oee.admin.stop-codes.index', $team));
});

test('users without dashboard access are sent to productos when that is their first section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Skus->value],
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertRedirect(route('oee.admin.skus.index', $team));
});

test('users with no plant section see the empty dashboard', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [],
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('noAccess', true)
            ->has('pendingInvitations', 0));
});
