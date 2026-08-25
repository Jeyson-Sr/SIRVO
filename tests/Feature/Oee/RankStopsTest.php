<?php

use App\Models\Team;
use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopRanking;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;
use App\Modules\Oee\Queries\RankStops;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->ranker = app(RankStops::class);

    $this->hour = OeeHourDetail::factory()
        ->for(OeeProduction::factory()->for($this->team)->on('2026-03-10'), 'production')
        ->create(['closed' => true]);
});

/**
 * Record downtime against a catalog code, repeating it as many times as asked.
 *
 * Each row counts once so the ranking totals stay predictable.
 */
function recordStops(OeeHourDetail $hour, CodStop $code, float $minutes, int $times = 1): void
{
    OeeStopDetail::factory()
        ->count($times)
        ->for($hour, 'hour')
        ->forCode($code)
        ->create(['tiempo_minutos' => $minutes, 'frecuencia' => 1]);
}

test('codes are ranked by the minutes they cost', function () {
    $costly = CodStop::factory()->ofType(StopType::Equipment)->create(['codigo' => 'EQ01']);
    $cheap = CodStop::factory()->ofType(StopType::Quality)->create(['codigo' => 'QD01']);

    recordStops($this->hour, $costly, minutes: 30);
    recordStops($this->hour, $cheap, minutes: 10);

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking[0]['codigo'])->toBe('EQ01')
        ->and($ranking[0]['totalMinutos'])->toBe(30.0)
        ->and($ranking[1]['codigo'])->toBe('QD01');
});

test('codes can be ranked by how often they happen instead', function () {
    $longAndRare = CodStop::factory()->create(['codigo' => 'EQ01']);
    $shortAndFrequent = CodStop::factory()->create(['codigo' => 'OR01']);

    recordStops($this->hour, $longAndRare, minutes: 30);
    recordStops($this->hour, $shortAndFrequent, minutes: 2, times: 5);

    $ranking = $this->ranker->handle(
        $this->team,
        ProductionFilters::fromArray([]),
        StopRanking::Frequency,
    );

    expect($ranking[0]['codigo'])->toBe('OR01')
        ->and($ranking[0]['totalFrecuencia'])->toBe(5);
});

test('repeated stops of one code are added together', function () {
    $code = CodStop::factory()->create(['codigo' => 'EQ01']);

    recordStops($this->hour, $code, minutes: 5, times: 4);

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking)->toHaveCount(1)
        ->and($ranking[0]['totalMinutos'])->toBe(20.0)
        ->and($ranking[0]['totalFrecuencia'])->toBe(4);
});

test('a continuation does not add another occurrence of the same code', function () {
    $code = CodStop::factory()->create(['codigo' => 'EQ01']);
    $nextHour = OeeHourDetail::factory()
        ->for($this->hour->production, 'production')
        ->atHour(1)
        ->create(['closed' => true]);

    OeeStopDetail::factory()->for($this->hour, 'hour')->forCode($code)->create([
        'tiempo_minutos' => 18,
        'frecuencia' => 1,
    ]);

    OeeStopDetail::factory()->for($nextHour, 'hour')->forCode($code)->continuation()->create([
        'tiempo_minutos' => 12,
    ]);

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking[0]['totalFrecuencia'])->toBe(1)
        ->and($ranking[0]['totalMinutos'])->toBe(30.0);
});

test('a stop logged with a repeat count contributes all of its occurrences', function () {
    $code = CodStop::factory()->create(['codigo' => 'EQ01']);

    // One row standing for three occurrences totalling nine minutes.
    OeeStopDetail::factory()->for($this->hour, 'hour')->forCode($code)->create([
        'tiempo_minutos' => 9,
        'frecuencia' => 3,
    ]);

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking[0]['totalFrecuencia'])->toBe(3)
        ->and($ranking[0]['totalMinutos'])->toBe(9.0);
});

test('the cumulative share builds a pareto curve reaching one hundred', function () {
    foreach (['A' => 50.0, 'B' => 30.0, 'C' => 20.0] as $suffix => $minutes) {
        recordStops(
            $this->hour,
            CodStop::factory()->create(['codigo' => 'EQ0'.$suffix]),
            minutes: $minutes,
        );
    }

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking[0]['porcentaje'])->toBe(50.0)
        ->and($ranking[0]['porcentajeAcumulado'])->toBe(50.0)
        ->and($ranking[1]['porcentajeAcumulado'])->toBe(80.0)
        ->and($ranking[2]['porcentajeAcumulado'])->toBe(100.0);
});

test('shares are measured against all downtime, not just the codes shown', function () {
    foreach (range(1, 5) as $index) {
        recordStops(
            $this->hour,
            CodStop::factory()->create(['codigo' => 'EQ0'.$index]),
            minutes: 10.0,
        );
    }

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]), limit: 2);

    // Each code is a fifth of the 50 minutes lost, even though only two are listed.
    expect($ranking)->toHaveCount(2)
        ->and($ranking[0]['porcentaje'])->toBe(20.0)
        ->and($ranking[1]['porcentajeAcumulado'])->toBe(40.0);
});

test('the ranking can be narrowed to one loss family', function () {
    recordStops($this->hour, CodStop::factory()->ofType(StopType::Equipment)->create(['codigo' => 'EQ01']), minutes: 30);
    recordStops($this->hour, CodStop::factory()->ofType(StopType::Quality)->create(['codigo' => 'QD01']), minutes: 10);

    $ranking = $this->ranker->handle(
        $this->team,
        ProductionFilters::fromArray(['componente' => StopType::Quality->value]),
    );

    expect($ranking)->toHaveCount(1)
        ->and($ranking[0]['codigo'])->toBe('QD01')
        // The share is of the filtered family, so it accounts for all of it.
        ->and($ranking[0]['porcentaje'])->toBe(100.0);
});

test('another team downtime never appears in the ranking', function () {
    $otherHour = OeeHourDetail::factory()
        ->for(OeeProduction::factory()->for(Team::factory())->on('2026-03-10'), 'production')
        ->create(['closed' => true]);

    recordStops($this->hour, CodStop::factory()->create(['codigo' => 'EQ01']), minutes: 10);
    recordStops($otherHour, CodStop::factory()->create(['codigo' => 'ZZ99']), minutes: 55);

    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking)->toHaveCount(1)
        ->and($ranking[0]['codigo'])->toBe('EQ01');
});

test('the ranking is capped so a request cannot ask for everything', function () {
    foreach (range(1, 12) as $index) {
        recordStops(
            $this->hour,
            CodStop::factory()->create(['codigo' => 'EQ'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]),
            minutes: 1.0,
        );
    }

    $ranking = $this->ranker->handle(
        $this->team,
        ProductionFilters::fromArray([]),
        limit: 9999,
    );

    expect(count($ranking))->toBeLessThanOrEqual(RankStops::MAX_LIMIT);
});

test('a period without downtime ranks nothing', function () {
    $ranking = $this->ranker->handle($this->team, ProductionFilters::fromArray([]));

    expect($ranking)->toBe([]);
});
