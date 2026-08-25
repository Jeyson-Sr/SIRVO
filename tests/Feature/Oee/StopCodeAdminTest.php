<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeStopDetail;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();

    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->engineer = User::factory()->create();
    $this->operator = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
    $this->team->members()->attach($this->engineer, ['role' => TeamRole::Engineer->value]);
    $this->team->members()->attach($this->operator, ['role' => TeamRole::Member->value]);
});

/**
 * @param  array<string, mixed>  $parameters
 */
function catalogRoute(string $name, Team $team, array $parameters = []): string
{
    return route($name, ['current_team' => $team->slug, ...$parameters]);
}

test('an owner can open the catalog admin', function () {
    CodStop::factory()->tetraPak()->create(['codigo' => 'TP01', 'detalle' => 'Selladora Tetra']);

    $this->actingAs($this->owner)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/stop-codes/index')
            ->has('types', 7)
            ->has('codes.data', 1)
            ->where('codes.data.0.codigo', 'TP01')
            ->where('codes.data.0.esTetraPak', true));
});

test('an admin can open the catalog admin', function () {
    $this->actingAs($this->admin)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/stop-codes/index'));
});

test('an engineer cannot open the catalog admin', function () {
    $this->actingAs($this->engineer)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team))
        ->assertForbidden();
});

test('an operator cannot open the catalog admin', function () {
    $this->actingAs($this->operator)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team))
        ->assertForbidden();
});

test('a stranger cannot open the catalog admin', function () {
    $this->actingAs($this->stranger)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team))
        ->assertForbidden();
});

test('the catalog can be filtered by loss tree and tetra pak', function () {
    CodStop::factory()->tetraPak()->ofType(StopType::Quality)->create(['codigo' => 'QD99']);
    CodStop::factory()->ofType(StopType::Equipment)->create(['codigo' => 'EQ99']);

    $this->actingAs($this->admin)
        ->get(catalogRoute('oee.admin.stop-codes.index', $this->team, [
            'tipo_parada' => 'QD',
            'es_tetra_pak' => '1',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/stop-codes/index')
            ->has('codes.data', 1)
            ->where('codes.data.0.codigo', 'QD99')
            ->where('filters.tipo_parada', 'QD')
            ->where('filters.es_tetra_pak', '1'));
});

test('an admin can create a stop code for a loss family', function () {
    $this->actingAs($this->admin)
        ->post(catalogRoute('oee.admin.stop-codes.store', $this->team), [
            'codigo' => 'opd88',
            'detalle' => 'Regulación de tapadora',
            'tipo_parada' => StopType::Operational->value,
            'es_tetra_pak' => '1',
            'activo' => '1',
        ])
        ->assertRedirect(catalogRoute('oee.admin.stop-codes.index', $this->team));

    $code = CodStop::query()->where('codigo', 'OPD88')->first();

    expect($code)->not->toBeNull()
        ->and($code->detalle)->toBe('Regulación de tapadora')
        ->and($code->tipo_parada)->toBe(StopType::Operational)
        ->and($code->es_tetra_pak)->toBeTrue()
        ->and($code->familia_oee)->toBe('OPD')
        ->and($code->categoria)->toBe('Operativas');
});

test('an operator cannot create a stop code', function () {
    $this->actingAs($this->operator)
        ->post(catalogRoute('oee.admin.stop-codes.store', $this->team), [
            'codigo' => 'XX01',
            'detalle' => 'No debería crearse',
            'tipo_parada' => StopType::Equipment->value,
            'es_tetra_pak' => '0',
            'activo' => '1',
        ])
        ->assertForbidden();

    expect(CodStop::query()->where('codigo', 'XX01')->exists())->toBeFalse();
});

test('an admin can change the loss tree and tetra pak flag', function () {
    $code = CodStop::factory()->ofType(StopType::Routine)->create([
        'codigo' => 'RD10',
        'detalle' => 'Limpieza',
        'es_tetra_pak' => false,
    ]);

    $this->actingAs($this->admin)
        ->put(catalogRoute('oee.admin.stop-codes.update', $this->team, ['codStop' => $code->id]), [
            'codigo' => 'RD10',
            'detalle' => 'Limpieza de llenadora',
            'tipo_parada' => StopType::Quality->value,
            'es_tetra_pak' => '1',
            'activo' => '1',
        ])
        ->assertRedirect(catalogRoute('oee.admin.stop-codes.index', $this->team));

    $code->refresh();

    expect($code->detalle)->toBe('Limpieza de llenadora')
        ->and($code->tipo_parada)->toBe(StopType::Quality)
        ->and($code->es_tetra_pak)->toBeTrue();
});

test('an unused stop code can be deleted', function () {
    $code = CodStop::factory()->create(['codigo' => 'DEL1']);

    $this->actingAs($this->owner)
        ->delete(catalogRoute('oee.admin.stop-codes.destroy', $this->team, ['codStop' => $code->id]))
        ->assertRedirect(catalogRoute('oee.admin.stop-codes.index', $this->team));

    expect(CodStop::query()->whereKey($code->id)->exists())->toBeFalse();
});

test('a stop code already used on a shift cannot be deleted', function () {
    $code = CodStop::factory()->create(['codigo' => 'USED1']);
    OeeStopDetail::factory()->forCode($code)->create();

    $this->actingAs($this->owner)
        ->from(catalogRoute('oee.admin.stop-codes.edit', $this->team, ['codStop' => $code->id]))
        ->delete(catalogRoute('oee.admin.stop-codes.destroy', $this->team, ['codStop' => $code->id]))
        ->assertRedirect(catalogRoute('oee.admin.stop-codes.edit', $this->team, ['codStop' => $code->id]))
        ->assertSessionHasErrors('codigo');

    expect(CodStop::query()->whereKey($code->id)->exists())->toBeTrue();
});

test('creating a stop code requires a unique code and a loss family', function () {
    CodStop::factory()->create(['codigo' => 'DUP1']);

    $this->actingAs($this->admin)
        ->from(catalogRoute('oee.admin.stop-codes.create', $this->team))
        ->post(catalogRoute('oee.admin.stop-codes.store', $this->team), [
            'codigo' => 'DUP1',
            'detalle' => '',
            'tipo_parada' => 'NOPE',
            'es_tetra_pak' => '0',
            'activo' => '1',
        ])
        ->assertRedirect(catalogRoute('oee.admin.stop-codes.create', $this->team))
        ->assertSessionHasErrors(['codigo', 'detalle', 'tipo_parada']);
});
