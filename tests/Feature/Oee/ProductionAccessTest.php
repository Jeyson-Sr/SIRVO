<?php

use App\Enums\TeamRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    // These cases are about who may reach each screen, not about how it is bundled.
    $this->withoutVite();

    $this->team = Team::factory()->create();

    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->member, ['role' => TeamRole::Member->value]);

    $this->production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create();
});

/**
 * Build a team-scoped OEE route for the current test team.
 *
 * @param  array<string, mixed>  $parameters
 */
function oeeRoute(string $name, Team $team, array $parameters = []): string
{
    return route($name, ['current_team' => $team->slug, ...$parameters]);
}

test('a member can open the dashboard', function () {
    $response = $this->actingAs($this->member)->get(oeeRoute('oee.dashboard', $this->team));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('oee/dashboard')->has('appliedFilters')
    );
});

test('the dashboard defers its heavy figures to a follow up request', function () {
    $response = $this->actingAs($this->member)->get(oeeRoute('oee.dashboard', $this->team));

    // The shell must paint without waiting on the aggregation.
    $response->assertInertia(fn (AssertableInertia $page) => $page->missing('report'));
});

test('the deferred figures arrive on the follow up request', function () {
    $hour = OeeHourDetail::factory()->for($this->production, 'production')->create([
        'hour_index' => 0,
        'estimado' => 100,
        'producido' => 100,
        'closed' => true,
    ]);

    $response = $this->actingAs($this->member)
        ->withHeaders([
            'X-Inertia' => 'true',
            // A stale asset version is answered with a 409, never the props.
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()),
            'X-Inertia-Partial-Component' => 'oee/dashboard',
            'X-Inertia-Partial-Data' => 'report',
        ])
        ->get(oeeRoute('oee.dashboard', $this->team));

    // A partial reload answers with the page object as plain JSON.
    // An hour that met its target in full leaves nothing for the losses.
    $response->assertOk()
        ->assertJsonPath('props.report.summary.closedHours', 1)
        ->assertJsonPath('props.report.summary.oee', 100)
        ->assertJsonCount(1, 'props.report.byLine')
        ->assertJsonCount(1, 'props.report.byDay');

    expect($hour->fresh()->minutos_pendientes)->toBe(0.0);
});

test('a stranger cannot open the dashboard of a team', function () {
    $response = $this->actingAs($this->stranger)->get(oeeRoute('oee.dashboard', $this->team));

    $response->assertForbidden();
});

test('an operator cannot list or open recorded shifts', function () {
    $this->actingAs($this->member)
        ->get(oeeRoute('oee.productions.index', $this->team))
        ->assertForbidden();

    $this->actingAs($this->member)
        ->get(oeeRoute('oee.productions.show', $this->team, ['production' => $this->production->id]))
        ->assertForbidden();
});

test('an owner can list and open the production of their team', function () {
    $this->actingAs($this->owner)
        ->get(oeeRoute('oee.productions.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/productions/index')
            ->has('productions.data'));

    $this->actingAs($this->owner)
        ->get(oeeRoute('oee.productions.show', $this->team, ['production' => $this->production->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/productions/show')
            ->where('production.id', $this->production->id)
            ->has('production.hours'));
});

test('the recording screen ships the hour ranges of both shifts', function () {
    $response = $this->actingAs($this->owner)->get(oeeRoute('oee.productions.create', $this->team));

    // The screen builds its twelve rows from these, so they must always arrive.
    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('oee/productions/create')
        ->has('shifts', 2)
        ->has('hourRanges.DIA', 12)
        ->has('hourRanges.NOCHE', 12)
        ->has('lines')
        ->where('ingeniero', $this->owner->name));
});

test('a stranger cannot open a production run', function () {
    $response = $this->actingAs($this->stranger)->get(
        oeeRoute('oee.productions.show', $this->team, ['production' => $this->production->id]),
    );

    $response->assertForbidden();
});

test('an inertia visit without permission sees the forbidden panel', function () {
    $this->actingAs($this->member)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()),
        ])
        ->get(oeeRoute('oee.productions.show', $this->team, ['production' => $this->production->id]))
        ->assertForbidden()
        ->assertJsonPath('component', 'errors/403')
        ->assertJsonPath('props.fallbackUrl', fn (string $url) => $url !== '');
});

test('a production cannot be reached through a team that does not own it', function () {
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);

    // The user belongs to both teams, so only the scoping stops the mismatch.
    $response = $this->actingAs($this->owner)->get(
        oeeRoute('oee.productions.show', $otherTeam, ['production' => $this->production->id]),
    );

    $response->assertNotFound();
});

test('a shift can only be closed once every hour is closed', function () {
    OeeHourDetail::factory()->for($this->production, 'production')->create([
        'hour_index' => 0,
        'closed' => false,
    ]);

    $response = $this->actingAs($this->owner)->post(
        oeeRoute('oee.productions.close', $this->team, ['production' => $this->production->id]),
    );

    $response->assertSessionHasErrors('production');

    expect($this->production->fresh()->isClosed())->toBeFalse();
});

test('an owner can close a shift once its hours are done', function () {
    OeeHourDetail::factory()->for($this->production, 'production')->create([
        'hour_index' => 0,
        'closed' => true,
    ]);

    $this->actingAs($this->owner)->post(
        oeeRoute('oee.productions.close', $this->team, ['production' => $this->production->id]),
    );

    expect($this->production->fresh()->isClosed())->toBeTrue();
});

test('a member cannot reopen a signed off shift', function () {
    $this->production->update(['closed_at' => now()]);

    $response = $this->actingAs($this->member)->post(
        oeeRoute('oee.productions.reopen', $this->team, ['production' => $this->production->id]),
    );

    $response->assertForbidden();

    expect($this->production->fresh()->isClosed())->toBeTrue();
});

test('an owner can reopen a signed off shift to correct it', function () {
    $this->production->update(['closed_at' => now()]);

    $this->actingAs($this->owner)->post(
        oeeRoute('oee.productions.reopen', $this->team, ['production' => $this->production->id]),
    );

    expect($this->production->fresh()->isClosed())->toBeFalse();
});

test('the stop code catalog can be searched by code or wording', function () {
    CodStop::factory()->create(['codigo' => 'EQ07', 'detalle' => 'Falla de llenadora']);
    CodStop::factory()->create(['codigo' => 'QD03', 'detalle' => 'Botella deformada']);

    $byCode = $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, ['search' => 'EQ07']));

    $byCode->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.codigo', 'EQ07');

    $byWording = $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, ['search' => 'llenadora']));

    $byWording->assertOk()->assertJsonPath('data.0.codigo', 'EQ07');
});

test('the stop code catalog can be filtered by loss family', function () {
    CodStop::factory()->ofType(StopType::Equipment)->create([
        'codigo' => 'EQ07',
        'detalle' => 'Falla de llenadora',
    ]);
    CodStop::factory()->ofType(StopType::Quality)->create([
        'codigo' => 'QD03',
        'detalle' => 'Botella deformada',
    ]);

    $response = $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, ['tipo' => 'EQ']));

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.codigo', 'EQ07');
});

test('searching stop codes stays inside the chosen loss family', function () {
    CodStop::factory()->ofType(StopType::Equipment)->create([
        'codigo' => 'A1',
        'detalle' => 'Calibración del aplicador',
    ]);
    CodStop::factory()->ofType(StopType::Operational)->create([
        'codigo' => 'B1',
        'detalle' => 'Calibración de sinfin',
    ]);

    $response = $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, [
            'search' => 'Calibración',
            'tipo' => 'EQ',
        ]));

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.codigo', 'A1');
});

test('an unknown loss family is rejected when searching stop codes', function () {
    $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, ['tipo' => 'NOPE']))
        ->assertUnprocessable();
});

test('retired stop codes are not offered for selection', function () {
    CodStop::factory()->inactive()->create(['codigo' => 'OLD01', 'detalle' => 'Código retirado']);

    $response = $this->actingAs($this->owner)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team, ['search' => 'OLD01']));

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('a stranger cannot browse the stop code catalog', function () {
    $response = $this->actingAs($this->stranger)
        ->getJson(oeeRoute('oee.stop-codes.index', $this->team));

    $response->assertForbidden();
});
