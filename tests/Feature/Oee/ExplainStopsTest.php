<?php

use App\Models\Team;
use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;
use App\Modules\Oee\Queries\ExplainStops;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->explainer = app(ExplainStops::class);
});

/**
 * Record a stop against a catalog code on a closed hour of the given run.
 */
function explainStop(
    OeeProduction $production,
    CodStop $code,
    float $minutes,
    ?string $comment = null,
    int $frequency = 1,
    ?string $hourSku = null,
): void {
    $hour = OeeHourDetail::factory()
        ->for($production, 'production')
        ->create([
            'closed' => true,
            'sku' => $hourSku,
            'hour_index' => $production->hours()->count(),
        ]);

    OeeStopDetail::factory()
        ->for($hour, 'hour')
        ->forCode($code)
        ->create([
            'tiempo_minutos' => $minutes,
            'frecuencia' => $frequency,
            'comentario' => $comment,
        ]);
}

test('ranked codes include the comments written against them', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'linea' => 'L3',
        'sku' => '88001',
        'descripcion' => 'Jugo naranja 500 ml',
    ]);
    $code = CodStop::factory()->create(['codigo' => 'J17', 'detalle' => 'CAMBIO DE SABOR']);

    explainStop($production, $code, minutes: 12, comment: 'Cambio a naranja');
    explainStop($production, $code, minutes: 8, comment: 'Cambio a piña');

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));

    expect($codes)->toHaveCount(1)
        ->and($codes[0]['codigo'])->toBe('J17')
        ->and($codes[0]['totalMinutos'])->toBe(20.0)
        ->and($codes[0]['totalFrecuencia'])->toBe(2)
        ->and($codes[0]['comentarios'])->toHaveCount(2)
        ->and(collect($codes[0]['comentarios'])->pluck('comentario')->all())
        ->toContain('Cambio a naranja')
        ->toContain('Cambio a piña');
});

test('each comment keeps the date and hour it happened', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create();
    $code = CodStop::factory()->create(['codigo' => 'J17']);
    $hour = OeeHourDetail::factory()
        ->for($production, 'production')
        ->create([
            'closed' => true,
            'hour_index' => 1,
            'hour_range' => '07:30 - 08:30',
        ]);

    OeeStopDetail::factory()
        ->for($hour, 'hour')
        ->forCode($code)
        ->create([
            'tiempo_minutos' => 12,
            'frecuencia' => 1,
            'comentario' => 'Cambio a naranja',
            'registered_at' => '2026-03-10 08:12:00',
        ]);

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));

    expect($codes[0]['comentarios'])->toHaveCount(1)
        ->and($codes[0]['comentarios'][0]['comentario'])->toBe('Cambio a naranja')
        ->and($codes[0]['comentarios'][0]['fecha'])->toBe('2026-03-10')
        ->and($codes[0]['comentarios'][0]['hora'])->toBe('07:30 - 08:30');
});

test('repeated comments of the same wording stay as separate events', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create();
    $code = CodStop::factory()->create(['codigo' => 'EQ01']);

    explainStop($production, $code, minutes: 5, comment: 'Torpedo gastado', frequency: 1);
    explainStop($production, $code, minutes: 7, comment: 'Torpedo gastado', frequency: 2);

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));
    $comments = collect($codes[0]['comentarios']);

    expect($comments)->toHaveCount(2)
        ->and($comments->pluck('comentario')->unique()->all())->toBe(['Torpedo gastado'])
        ->and($comments->sum('totalMinutos'))->toBe(12.0)
        ->and($comments->sum('totalFrecuencia'))->toBe(3);
});

test('each code reports the products and lines it stopped', function () {
    $orange = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'linea' => 'L3',
        'sku' => '88001',
        'descripcion' => 'Jugo naranja 500 ml',
    ]);
    $cola = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'linea' => 'L5',
        'sku' => '77002',
        'descripcion' => 'Cola 625 ml',
    ]);
    $code = CodStop::factory()->create(['codigo' => 'J17']);

    explainStop($orange, $code, minutes: 10, comment: 'Cambio a naranja', frequency: 2);
    explainStop($cola, $code, minutes: 4, comment: 'Cambio a cola');

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));

    expect($codes[0]['productos'])->toHaveCount(2)
        ->and($codes[0]['productos'][0]['sku'])->toBe('88001')
        ->and($codes[0]['productos'][0]['producto'])->toBe('Jugo naranja 500 ml')
        ->and($codes[0]['productos'][0]['totalFrecuencia'])->toBe(2)
        ->and($codes[0]['lineas'])->toHaveCount(2)
        ->and(collect($codes[0]['lineas'])->pluck('linea')->all())->toContain('L3', 'L5')
        ->and($codes[0]['ocurrencias'])->toHaveCount(2)
        ->and($codes[0]['ocurrencias'][0]['linea'])->toBe('L3')
        ->and($codes[0]['ocurrencias'][0]['sku'])->toBe('88001');
});

test('an hour sku overrides the production sku when the product changed', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'linea' => 'L1',
        'sku' => '88001',
        'descripcion' => 'Jugo naranja 500 ml',
    ]);
    $code = CodStop::factory()->create(['codigo' => 'J17']);

    explainStop($production, $code, minutes: 6, comment: 'Cambio de sabor', hourSku: '99009');

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));

    expect($codes[0]['productos'][0]['sku'])->toBe('99009');
});

test('another team downtime never appears in the comments', function () {
    $ours = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create();
    $theirs = OeeProduction::factory()->for(Team::factory())->on('2026-03-10')->create();
    $oursCode = CodStop::factory()->create(['codigo' => 'EQ01']);
    $theirsCode = CodStop::factory()->create(['codigo' => 'ZZ99']);

    explainStop($ours, $oursCode, minutes: 10, comment: 'Nuestra parada');
    explainStop($theirs, $theirsCode, minutes: 55, comment: 'Parada ajena');

    $codes = $this->explainer->handle($this->team, ProductionFilters::fromArray([]));

    expect($codes)->toHaveCount(1)
        ->and($codes[0]['codigo'])->toBe('EQ01')
        ->and($codes[0]['comentarios'][0]['comentario'])->toBe('Nuestra parada');
});

test('a period without downtime explains nothing', function () {
    expect($this->explainer->handle($this->team, ProductionFilters::fromArray([])))->toBe([]);
});
