<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeStopDetail;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->operator = User::factory()->create();
    $this->team->members()->attach($this->operator, ['role' => TeamRole::Admin->value]);

    $this->equipmentCode = CodStop::factory()->ofType(StopType::Equipment)->create(['codigo' => 'EQ01']);
    $this->qualityCode = CodStop::factory()->ofType(StopType::Quality)->create(['codigo' => 'QD01']);
});

/**
 * Build a valid sync payload, letting each test override just what it cares about.
 *
 * @param  array<int, array<string, mixed>>  $hours
 * @param  array<string, mixed>  $production
 * @return array<string, mixed>
 */
function syncPayload(array $hours = [], array $production = []): array
{
    return [
        'production' => [
            'fecha' => '2026-03-15',
            'turno' => Shift::Day->value,
            'linea' => 'L4',
            'op' => '424',
            'ingeniero' => 'Ana Torres',
            'operador' => 'Luis Ramos',
            'sku' => '10234',
            'marca' => 'KR',
            'pallets_por_hora' => 18.5,
            'bph' => 24000,
            ...$production,
        ],
        'hours' => $hours === [] ? [hourPayload()] : $hours,
    ];
}

/**
 * Build a valid hour slot for a sync payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function hourPayload(array $overrides = []): array
{
    return [
        'hour_index' => 0,
        'hour_range' => '06:30 - 07:30',
        'estimado' => 20,
        'producido' => 20,
        'closed' => true,
        'stops' => [],
        ...$overrides,
    ];
}

/**
 * Build a valid stop for an hour slot.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stopPayload(array $overrides = []): array
{
    return [
        'client_uuid' => (string) Str::uuid(),
        'codigo' => 'EQ01',
        'tiempo_minutos' => 12,
        'frecuencia' => 1,
        ...$overrides,
    ];
}

test('a team member can record a shift', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [stopPayload()]])]),
    );

    $production = OeeProduction::firstOrFail();

    $response->assertRedirect(route('oee.productions.show', [
        'current_team' => $this->team->slug,
        'production' => $production->id,
    ]));

    expect($production->team_id)->toBe($this->team->id)
        ->and($production->created_by)->toBe($this->operator->id)
        ->and($production->linea)->toBe('L4')
        ->and($production->hours)->toHaveCount(1)
        ->and($production->hours->first()->stops)->toHaveCount(1);
});

test('the production order is normalised on the way in', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(production: ['op' => 'OP-424']),
    );

    expect(OeeProduction::firstOrFail()->op)->toBe('2026000424');
});

test('resubmitting the same shift updates it instead of duplicating it', function () {
    $stop = stopPayload();
    $payload = syncPayload([hourPayload(['stops' => [$stop]])]);

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        $payload,
    );

    $firstId = OeeProduction::firstOrFail()->id;
    $firstStopId = OeeStopDetail::firstOrFail()->id;

    // The same submission arrives again, as a retry or a double click would.
    $payload['hours'][0]['producido'] = 19;
    $payload['hours'][0]['stops'][0]['tiempo_minutos'] = 15;

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        $payload,
    );

    expect(OeeProduction::count())->toBe(1)
        ->and(OeeProduction::firstOrFail()->id)->toBe($firstId)
        ->and(OeeStopDetail::count())->toBe(1);

    $persistedStop = OeeStopDetail::firstOrFail();

    // The row keeps its identity, so anything referencing it stays valid.
    expect($persistedStop->id)->toBe($firstStopId)
        ->and((float) $persistedStop->tiempo_minutos)->toBe(15.0);
});

test('only the stops the operator removed are deleted', function () {
    $keptStop = stopPayload(['codigo' => 'EQ01']);
    $removedStop = stopPayload(['codigo' => 'QD01', 'tiempo_minutos' => 5]);

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [$keptStop, $removedStop]])]),
    );

    expect(OeeStopDetail::count())->toBe(2);

    $keptStopId = OeeStopDetail::where('client_uuid', $keptStop['client_uuid'])->firstOrFail()->id;

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [$keptStop]])]),
    );

    expect(OeeStopDetail::count())->toBe(1)
        ->and(OeeStopDetail::firstOrFail()->id)->toBe($keptStopId);
});

test('the loss family is taken from the catalog and not from the client', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [
            stopPayload(['codigo' => 'QD01', 'tipo' => StopType::Unscheduled->value]),
        ]])]),
    );

    // Were this taken from the payload, the downtime would escape the OEE penalty.
    expect(OeeStopDetail::firstOrFail()->tipo)->toBe(StopType::Quality);
});

test('an hour that was closed cannot be reopened by a later submission', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['closed' => true])]),
    );

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['closed' => false])]),
    );

    expect(OeeProduction::firstOrFail()->hours->first()->closed)->toBeTrue();
});

test('a signed off shift rejects further changes', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(),
    );

    OeeProduction::firstOrFail()->update(['closed_at' => now()]);

    $member = User::factory()->create();
    $this->team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this->actingAs($member)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['producido' => 20])]),
    );

    $response->assertForbidden();

    expect((float) OeeProduction::firstOrFail()->hours->first()->producido)->toBe(20.0);
});

test('downtime cannot exceed the hour it belongs to', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [
            stopPayload(['tiempo_minutos' => 40]),
            stopPayload(['tiempo_minutos' => 25]),
        ]])]),
    );

    $response->assertSessionHasErrors('hours.0.stops');

    expect(OeeProduction::count())->toBe(0);
});

test('produced output cannot exceed the hour target', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload([
            'estimado' => 7,
            'producido' => 8,
        ])]),
    );

    $response->assertSessionHasErrors('hours.0.producido');

    expect(OeeProduction::count())->toBe(0);
});

test('a stop code outside the catalog is rejected', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [stopPayload(['codigo' => 'NOPE99'])]])]),
    );

    $response->assertSessionHasErrors('hours.0.stops.0.codigo');
});

test('a retired stop code is rejected', function () {
    CodStop::factory()->inactive()->create(['codigo' => 'OLD01']);

    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [stopPayload(['codigo' => 'OLD01'])]])]),
    );

    $response->assertSessionHasErrors('hours.0.stops.0.codigo');
});

test('stop codes are matched regardless of the casing sent', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [stopPayload(['codigo' => ' eq01 '])]])]),
    );

    expect(OeeStopDetail::firstOrFail()->codigo)->toBe('EQ01');
});

test('two hours cannot share the same slot', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([
            hourPayload(['hour_index' => 3]),
            hourPayload(['hour_index' => 3]),
        ]),
    );

    $response->assertSessionHasErrors('hours.1.hour_index');
});

test('a product change stores two slices with their own duration and sku', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([
            hourPayload([
                'hour_index' => 0,
                'hour_range' => '11:30 - 12:00',
                'duration_minutes' => 30,
                'sku' => '408462',
                'pallets_por_hora' => 20,
                'estimado' => 10,
                'producido' => 10,
            ]),
            hourPayload([
                'hour_index' => 1,
                'hour_range' => '12:00 - 12:30',
                'duration_minutes' => 30,
                'sku' => '408469',
                'pallets_por_hora' => 28.6,
                'estimado' => 14.3,
                'producido' => 14.3,
            ]),
        ]),
    );

    $hours = OeeProduction::firstOrFail()->hours;

    expect($hours)->toHaveCount(2)
        ->and((float) $hours[0]->duration_minutes)->toBe(30.0)
        ->and($hours[0]->sku)->toBe('408462')
        ->and((float) $hours[0]->estimado)->toBe(10.0)
        ->and((float) $hours[1]->duration_minutes)->toBe(30.0)
        ->and($hours[1]->sku)->toBe('408469')
        ->and((float) $hours[1]->estimado)->toBe(14.3);
});

test('a shift cannot be recorded for a future date', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(production: ['fecha' => now()->addDay()->toDateString()]),
    );

    $response->assertSessionHasErrors('production.fecha');
});

test('typing a sku fills the product sheet and the hourly target', function () {
    OeeSku::factory()->create([
        'sku' => '408462',
        'descripcion' => 'CIELO AGUA SIN GAS PET NO RETORNABLE 625 ml 15 pack',
        'formato' => '0.625',
        'marca' => 'CIELO',
        'sabor' => 'AGUA',
        'pallets_por_hora' => 28.6,
        'bph' => 60000,
    ]);

    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(
            [hourPayload(['estimado' => null, 'producido' => 28.6])],
            [
                'sku' => '408462',
                'descripcion' => null,
                'marca' => null,
                'pallets_por_hora' => null,
                'bph' => null,
            ],
        ),
    );

    $production = OeeProduction::firstOrFail();

    expect($production->descripcion)->toBe('CIELO AGUA SIN GAS PET NO RETORNABLE 625 ml 15 pack')
        ->and($production->marca)->toBe('CIELO')
        ->and((float) $production->pallets_por_hora)->toBe(28.6)
        ->and((float) $production->hours->first()->estimado)->toBe(28.6);
});

test('typing a stop code fills the minutes the hour still owes', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload([
            'estimado' => 20,
            'producido' => 10,
            'stops' => [stopPayload(['tiempo_minutos' => null])],
        ])]),
    );

    $stop = OeeStopDetail::firstOrFail();

    expect((float) $stop->tiempo_minutos)->toBe(30.0)
        ->and($stop->tipo)->toBe(StopType::Equipment)
        ->and($stop->codigo)->toBe('EQ01');
});

test('a stop comment is persisted with the code', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload(['stops' => [
            stopPayload(['comentario' => 'Torpedo gastado en llenadora']),
        ]])]),
    );

    expect(OeeStopDetail::firstOrFail()->comentario)->toBe('Torpedo gastado en llenadora')
        ->and(OeeStopDetail::firstOrFail()->continua)->toBeFalse();
});

test('a continuation of the previous hour counts once', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([
            hourPayload([
                'hour_index' => 0,
                'hour_range' => '06:30 - 07:30',
                'stops' => [stopPayload([
                    'comentario' => 'Cambio de torpedo',
                    'frecuencia' => 1,
                ])],
            ]),
            hourPayload([
                'hour_index' => 1,
                'hour_range' => '07:30 - 08:30',
                'stops' => [stopPayload([
                    'comentario' => 'Cambio de torpedo',
                    'continua' => true,
                    'frecuencia' => 1,
                ])],
            ]),
        ]),
    );

    $stops = OeeStopDetail::query()->orderBy('id')->get();

    expect($stops)->toHaveCount(2)
        ->and($stops[0]->frecuencia)->toBe(1)
        ->and($stops[0]->continua)->toBeFalse()
        ->and($stops[1]->frecuencia)->toBe(0)
        ->and($stops[1]->continua)->toBeTrue()
        ->and($stops[1]->comentario)->toBe('Cambio de torpedo');
});

test('the shift header is required before produced output can be recorded', function (string $field) {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(production: [$field => '']),
    );

    $response->assertSessionHasErrors("production.{$field}");

    expect(OeeProduction::count())->toBe(0);
})->with([
    'sku',
    'operador',
    'op',
]);

test('the engineer is taken from the logged-in user', function () {
    $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(production: ['ingeniero' => '']),
    );

    expect(OeeProduction::firstOrFail()->ingeniero)->toBe($this->operator->name);
});

test('a closed hour is rejected when the shortfall is still unexplained', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([hourPayload([
            'producido' => 10,
            'closed' => true,
            'stops' => [],
        ])]),
    );

    $response->assertSessionHasErrors('hours.0.stops');

    expect(OeeProduction::count())->toBe(0);
});

test('a later hour is rejected until the previous one is balanced', function () {
    $response = $this->actingAs($this->operator)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload([
            hourPayload([
                'hour_index' => 0,
                'producido' => 10,
                'closed' => false,
                'stops' => [],
            ]),
            hourPayload([
                'hour_index' => 1,
                'hour_range' => '07:30 - 08:30',
                'producido' => 20,
                'closed' => true,
            ]),
        ]),
    );

    $response->assertSessionHasErrors([
        'hours.1.producido' => 'Por favor cuadra los tiempos de la hora anterior.',
    ]);

    expect(OeeProduction::count())->toBe(0);
});

test('a stranger cannot record production for a team', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(),
    );

    $response->assertForbidden();

    expect(OeeProduction::count())->toBe(0);
});

test('guests are sent to the login screen', function () {
    $response = $this->post(
        route('oee.productions.store', ['current_team' => $this->team->slug]),
        syncPayload(),
    );

    $response->assertRedirect(route('login'));
});
