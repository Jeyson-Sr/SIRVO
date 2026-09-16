<?php

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use App\Modules\Oee\Models\OeeProduction;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create();
    $this->operator = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
    $this->team->members()->attach($this->operator, [
        'role' => TeamRole::Member->value,
        'sections' => [AppSection::Dashboard->value],
    ]);
});

test('an admin can open the users access panel', function () {
    $this->actingAs($this->admin)
        ->get(route('oee.admin.users.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/users/index')
            ->has('sections', 6)
            ->has('roles', 3)
            ->has('users', 3));
});

test('an admin can open the create user form', function () {
    $this->actingAs($this->admin)
        ->get(route('oee.admin.users.create', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/users/create')
            ->has('sections', 6)
            ->missing('roles'));
});

test('an admin can create a user in the users table', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Carla Operadora',
            'email' => 'carla@planta.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $user = User::query()->where('email', 'carla@planta.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Carla Operadora')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->current_team_id)->toBe($this->team->id);

    $membership = $this->team->memberships()->where('user_id', $user->id)->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(TeamRole::Member)
        ->and($membership->grantedSections())->toBe([
            AppSection::Dashboard->value,
        ]);
});

test('creating a user validates unique email against the users table', function () {
    $this->actingAs($this->admin)
        ->from(route('oee.admin.users.create', $this->team))
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Duplicado',
            'email' => $this->operator->email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => TeamRole::Member->value,
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.create', $this->team))
        ->assertSessionHasErrors('email');
});

test('creating a user requires a valid name email and password', function (array $override, string $field) {
    $this->actingAs($this->admin)
        ->from(route('oee.admin.users.create', $this->team))
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Nuevo',
            'email' => 'nuevo@planta.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'sections' => [OeeSection::Oee->value],
            ...$override,
        ])
        ->assertRedirect(route('oee.admin.users.create', $this->team))
        ->assertSessionHasErrors($field);
})->with([
    'name' => [['name' => ''], 'name'],
    'email' => [['email' => 'no-es-correo'], 'email'],
    'password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
]);

test('a new user is always created as an operator even if another role is sent', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Forzado',
            'email' => 'forzado@planta.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => TeamRole::Engineer->value,
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $user = User::query()->where('email', 'forzado@planta.test')->first();

    expect($this->team->memberships()->where('user_id', $user?->id)->first()?->role)
        ->toBe(TeamRole::Member);
});

test('an operator cannot create a user', function () {
    $this->actingAs($this->operator)
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Intruso',
            'email' => 'intruso@planta.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => TeamRole::Member->value,
        ])
        ->assertForbidden();

    expect(User::query()->where('email', 'intruso@planta.test')->exists())->toBeFalse();
});

test('an operator cannot open the users access panel', function () {
    $this->actingAs($this->operator)
        ->get(route('oee.admin.users.index', $this->team))
        ->assertForbidden();
});

test('an admin can delete a user who is not on the team', function () {
    $this->actingAs($this->admin)
        ->delete(route('oee.admin.users.destroy', [
            'current_team' => $this->team->slug,
            'user' => $this->outsider->id,
        ]))
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->assertModelMissing($this->outsider);
});

test('an admin can grant section access to an existing user', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->outsider->id,
        ]), [
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $membership = $this->team->memberships()->where('user_id', $this->outsider->id)->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(TeamRole::Member)
        ->and($membership->grantedSections())->toBe([
            AppSection::Dashboard->value,
        ])
        ->and($this->outsider->fresh()->current_team_id)->toBe($this->team->id);
});

test('an operator without the paradas section cannot open stop comments', function () {
    $this->actingAs($this->operator)
        ->get(route('oee.paradas', $this->team))
        ->assertForbidden();
});

test('an operator without the turnos section cannot open recorded shifts', function () {
    $this->actingAs($this->operator)
        ->get(route('oee.productions.index', $this->team))
        ->assertForbidden();
});

test('an operator without panel oee cannot open the oee panel', function () {
    $this->actingAs($this->operator)
        ->get(route('oee.dashboard', $this->team))
        ->assertForbidden();
});

test('an admin can revoke panel oee from an operator', function () {
    $this->team->memberships()
        ->where('user_id', $this->operator->id)
        ->update(['sections' => [OeeSection::Oee->value]]);

    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->actingAs($this->operator)
        ->get(route('oee.dashboard', $this->team))
        ->assertForbidden();
});

test('a new user without sections starts with panel oee only', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.users.store', $this->team), [
            'name' => 'Visor Nuevo',
            'email' => 'visor@planta.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $user = User::query()->where('email', 'visor@planta.test')->first();

    expect($this->team->memberships()->where('user_id', $user?->id)->first()?->grantedSections())
        ->toBe([OeeSection::Oee->value]);
});

test('an admin can assign the viewer role', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'role' => TeamRole::Viewer->value,
            'sections' => [AppSection::Dashboard->value, OeeSection::Oee->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    expect($this->team->memberships()->where('user_id', $this->operator->id)->first()?->role)
        ->toBe(TeamRole::Viewer);
});

test('an admin can delete an operator from the users table', function () {
    $this->actingAs($this->admin)
        ->delete(route('oee.admin.users.destroy', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]))
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->assertModelMissing($this->operator);
    expect($this->team->memberships()->where('user_id', $this->operator->id)->exists())->toBeFalse();
});

test('an admin cannot delete themselves', function () {
    $this->actingAs($this->admin)
        ->delete(route('oee.admin.users.destroy', [
            'current_team' => $this->team->slug,
            'user' => $this->admin->id,
        ]))
        ->assertForbidden();

    $this->assertModelExists($this->admin);
});

test('an admin cannot delete another administrator', function () {
    $otherAdmin = User::factory()->create();
    $this->team->members()->attach($otherAdmin, ['role' => TeamRole::Admin->value]);

    $this->actingAs($this->admin)
        ->delete(route('oee.admin.users.destroy', [
            'current_team' => $this->team->slug,
            'user' => $otherAdmin->id,
        ]))
        ->assertForbidden();

    $this->assertModelExists($otherAdmin);
});

test('an operator cannot delete a user', function () {
    $this->actingAs($this->operator)
        ->delete(route('oee.admin.users.destroy', [
            'current_team' => $this->team->slug,
            'user' => $this->admin->id,
        ]))
        ->assertForbidden();

    $this->assertModelExists($this->admin);
});

test('an admin can change an operator role to engineer', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'role' => TeamRole::Engineer->value,
            'sections' => [AppSection::Dashboard->value, OeeSection::Oee->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $membership = $this->team->memberships()->where('user_id', $this->operator->id)->first();

    expect($membership?->role)->toBe(TeamRole::Engineer)
        ->and($membership?->grantedSections())->toBe([
            AppSection::Dashboard->value,
            OeeSection::Oee->value,
        ]);
});

test('an admin cannot promote a user to administrator', function () {
    $this->actingAs($this->admin)
        ->from(route('oee.admin.users.index', $this->team))
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'role' => TeamRole::Admin->value,
            'sections' => [AppSection::Dashboard->value],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team))
        ->assertSessionHasErrors('role');
});

test('an operator without the turnos section cannot edit or delete a shift', function () {
    $production = OeeProduction::factory()->for($this->team)->create();

    $this->actingAs($this->operator)
        ->get(route('oee.productions.edit', [
            'current_team' => $this->team->slug,
            'production' => $production->id,
        ]))
        ->assertForbidden();

    $this->actingAs($this->operator)
        ->delete(route('oee.productions.destroy', [
            'current_team' => $this->team->slug,
            'production' => $production->id,
        ]))
        ->assertForbidden();
});

test('an admin can grant paradas view to an operator', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'sections' => [
                OeeSection::Oee->value,
                OeeSection::Paradas->value,
            ],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->actingAs($this->operator)
        ->get(route('oee.paradas', $this->team))
        ->assertOk();
});

test('an admin can grant turnos and stop-code view to an operator', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'sections' => [
                OeeSection::Oee->value,
                OeeSection::Productions->value,
                OeeSection::Catalog->value,
            ],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->actingAs($this->operator)
        ->get(route('oee.productions.index', $this->team))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('oee.admin.stop-codes.index', $this->team))
        ->assertOk();
});

test('an admin can grant productos view to an operator', function () {
    $this->actingAs($this->admin)
        ->put(route('oee.admin.users.update', [
            'current_team' => $this->team->slug,
            'user' => $this->operator->id,
        ]), [
            'sections' => [
                OeeSection::Oee->value,
                OeeSection::Skus->value,
            ],
        ])
        ->assertRedirect(route('oee.admin.users.index', $this->team));

    $this->actingAs($this->operator)
        ->get(route('oee.admin.skus.index', $this->team))
        ->assertOk();
});

test('an operator with catalog view cannot create a stop code', function () {
    $this->team->memberships()
        ->where('user_id', $this->operator->id)
        ->update(['sections' => [OeeSection::Catalog->value]]);

    $this->actingAs($this->operator)
        ->get(route('oee.admin.stop-codes.create', $this->team))
        ->assertForbidden();
});
