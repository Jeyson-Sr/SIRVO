<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create();
    $this->operator = User::factory()->create();

    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
    $this->team->members()->attach($this->operator, ['role' => TeamRole::Member->value]);

    $this->production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'turno' => Shift::Day,
        'linea' => 'L4',
        'op' => '424',
        'sku' => '10234',
        'operador' => 'Luis Ramos',
        'pallets_por_hora' => 18.5,
        'bph' => 24000,
    ]);

    OeeHourDetail::factory()->for($this->production, 'production')->create([
        'hour_index' => 0,
        'hour_range' => '06:30 - 07:30',
        'estimado' => 20,
        'producido' => 20,
        'closed' => true,
    ]);
});

test('an admin can open the edit screen of a recorded shift', function () {
    $this->actingAs($this->admin)
        ->get(route('oee.productions.edit', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/productions/create')
            ->where('productionId', $this->production->id)
            ->where('recording.production.op', '424')
            ->has('recording.hours'));
});

test('an operator cannot edit a signed off shift', function () {
    $this->production->update(['closed_at' => now()]);

    $this->actingAs($this->operator)
        ->get(route('oee.productions.edit', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]))
        ->assertForbidden();
});

test('an admin can correct a signed off shift', function () {
    $this->production->update(['closed_at' => now()]);

    $this->actingAs($this->admin)
        ->put(route('oee.productions.update', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]), [
            'production' => [
                'fecha' => '2026-03-10',
                'turno' => Shift::Day->value,
                'linea' => 'L4',
                'op' => '424',
                'ingeniero' => $this->admin->name,
                'operador' => 'Carlos Ruiz',
                'sku' => '10234',
                'pallets_por_hora' => 18.5,
                'bph' => 24000,
            ],
            'hours' => [[
                'hour_index' => 0,
                'hour_range' => '06:30 - 07:30',
                'estimado' => 20,
                'producido' => 20,
                'closed' => true,
                'stops' => [],
            ]],
        ])
        ->assertRedirect(route('oee.productions.show', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]));

    $production = $this->production->fresh();

    expect($production->operador)->toBe('Carlos Ruiz')
        ->and((float) $production->hours->first()->producido)->toBe(20.0)
        ->and($production->isClosed())->toBeTrue();
});

test('an admin can delete a recorded shift', function () {
    $this->actingAs($this->admin)
        ->delete(route('oee.productions.destroy', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]))
        ->assertRedirect(route('oee.productions.index', $this->team));

    expect(OeeProduction::query()->whereKey($this->production->id)->exists())->toBeFalse();
});

test('an operator cannot delete a recorded shift', function () {
    $this->actingAs($this->operator)
        ->delete(route('oee.productions.destroy', [
            'current_team' => $this->team->slug,
            'production' => $this->production->id,
        ]))
        ->assertForbidden();

    expect(OeeProduction::query()->whereKey($this->production->id)->exists())->toBeTrue();
});
