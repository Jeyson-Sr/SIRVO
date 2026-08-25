<?php

use App\Models\Team;
use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;
use App\Modules\Oee\Queries\BuildOeeReport;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->report = app(BuildOeeReport::class);
});

/**
 * Record one closed hour, optionally losing some minutes to a stop.
 */
function closedHour(
    OeeProduction $production,
    int $hourIndex = 0,
    float $produced = 100.0,
    ?StopType $lostTo = null,
    float $lostMinutes = 0.0,
): OeeHourDetail {
    $hour = OeeHourDetail::factory()->for($production, 'production')->create([
        'hour_index' => $hourIndex,
        'producido' => $produced,
        'closed' => true,
    ]);

    if ($lostTo !== null) {
        OeeStopDetail::factory()
            ->for($hour, 'hour')
            ->ofType($lostTo, $lostMinutes)
            ->create();
    }

    return $hour;
}

test('a team with nothing recorded reports zeroes rather than failing', function () {
    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['summary']['oee'])->toBe(0.0)
        ->and($report['volumen'])->toBe(0.0)
        ->and($report['byLine'])->toBe([])
        ->and($report['byDay'])->toBe([]);
});

test('the summary aggregates every closed hour of the team', function () {
    $production = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->create();

    closedHour($production, hourIndex: 0, produced: 100, lostTo: StopType::Equipment, lostMinutes: 30);
    closedHour($production, hourIndex: 1, produced: 120);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    // 120 scheduled minutes, 30 lost to equipment.
    expect($report['summary']['closedHours'])->toBe(2)
        ->and($report['summary']['oee'])->toBe(75.0)
        ->and($report['summary']['em'])->toBe(75.0)
        ->and($report['volumen'])->toBe(220.0);
});

test('volume converts closed pallets into consumer units from the sheet', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'formato' => '0.5',
        'pallets_por_hora' => 10,
        'bph' => 1000,
    ]);

    closedHour($production, produced: 10);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    // 10 PH × (1000 BPH / 10 PH) × 0.500 L / 30 = 16.67 CU.
    expect($report['volumen'])->toBe(16.67);
});

test('hours still open are left out of the figures', function () {
    $production = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->create();

    closedHour($production, hourIndex: 0, produced: 100);

    OeeHourDetail::factory()->for($production, 'production')->create([
        'hour_index' => 1,
        'producido' => 999,
        'closed' => false,
    ]);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['summary']['closedHours'])->toBe(1)
        ->and($report['volumen'])->toBe(100.0);
});

test('another team production never reaches the report', function () {
    $otherTeam = Team::factory()->create();

    $ourRun = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->create();
    closedHour($ourRun, produced: 100);

    $theirRun = OeeProduction::factory()->for($otherTeam)->on('2026-03-10')->create();
    closedHour($theirRun, produced: 5000, lostTo: StopType::Equipment, lostMinutes: 59);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['volumen'])->toBe(100.0)
        ->and($report['summary']['oee'])->toBe(100.0);
});

test('the figures are broken down per line, best first', function () {
    $strongLine = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->onLine('L1')->create();
    $weakLine = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->onLine('L2')->create();

    closedHour($strongLine, produced: 100);
    closedHour($weakLine, produced: 80, lostTo: StopType::Operational, lostMinutes: 30);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['byLine'])->toHaveCount(2)
        ->and($report['byLine'][0]['linea'])->toBe('L1')
        ->and($report['byLine'][0]['oee'])->toBe(100.0)
        ->and($report['byLine'][1]['linea'])->toBe('L2')
        ->and($report['byLine'][1]['oee'])->toBe(50.0);
});

test('the figures are broken down per day, oldest first', function () {
    $firstDay = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->create();
    $secondDay = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-12')->create();

    closedHour($firstDay, produced: 100);
    closedHour($secondDay, produced: 200);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['byDay'])->toHaveCount(2)
        ->and($report['byDay'][0]['fecha'])->toBe('2026-03-10')
        ->and($report['byDay'][0]['label'])->toBe('10/03')
        ->and($report['byDay'][1]['fecha'])->toBe('2026-03-12')
        ->and($report['byDay'][1]['volumen'])->toBe(200.0);
});

test('days are grouped into iso weeks', function () {
    // Sunday 15 March 2026 closes week 11; Monday the 16th opens week 12.
    $endOfWeek = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-15')->create();
    $startOfNextWeek = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-16')->create();

    closedHour($endOfWeek, produced: 100);
    closedHour($startOfNextWeek, produced: 300);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([]));

    expect($report['byWeek'])->toHaveCount(2)
        ->and($report['byWeek'][0]['semana'])->toBe('2026-W11')
        ->and($report['byWeek'][0]['volumen'])->toBe(100.0)
        ->and($report['byWeek'][1]['semana'])->toBe('2026-W12')
        ->and($report['byWeek'][1]['volumen'])->toBe(300.0);
});

test('a date range includes production recorded on its boundaries', function () {
    foreach (['2026-03-09', '2026-03-15', '2026-03-16'] as $date) {
        closedHour(
            OeeProduction::factory()->for($this->team)->knownSheet()->on($date)->create(),
            produced: 100,
        );
    }

    $report = $this->report->handle($this->team, ProductionFilters::fromArray([
        'from' => '2026-03-09',
        'to' => '2026-03-15',
    ]));

    // Both edge days count; only the 16th falls outside.
    expect($report['summary']['closedHours'])->toBe(2)
        ->and($report['volumen'])->toBe(200.0);
});

test('filtering by line narrows the whole report', function () {
    $wanted = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->onLine('L1')->create();
    $other = OeeProduction::factory()->for($this->team)->knownSheet()->on('2026-03-10')->onLine('L2')->create();

    closedHour($wanted, produced: 100);
    closedHour($other, produced: 500);

    $report = $this->report->handle($this->team, ProductionFilters::fromArray(['linea' => 'L1']));

    expect($report['volumen'])->toBe(100.0)
        ->and($report['byLine'])->toHaveCount(1);
});

test('the report costs the same number of queries however much data it spans', function () {
    $countQueriesForDays = function (int $days): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->report->handle($this->team, ProductionFilters::fromArray([]));

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    foreach (range(1, 3) as $day) {
        $production = OeeProduction::factory()
            ->for($this->team)
            ->on(sprintf('2026-03-%02d', $day))
            ->onLine('L'.$day)
            ->create();

        closedHour($production, produced: 100, lostTo: StopType::Equipment, lostMinutes: 10);
    }

    $smallReport = $countQueriesForDays(3);

    foreach (range(4, 12) as $day) {
        $production = OeeProduction::factory()
            ->for($this->team)
            ->on(sprintf('2026-03-%02d', $day))
            ->onLine('L'.$day)
            ->create();

        closedHour($production, produced: 100, lostTo: StopType::Equipment, lostMinutes: 10);
    }

    $largeReport = $countQueriesForDays(12);

    // Four times the lines and days must not mean four times the queries.
    expect($largeReport)->toBe($smallReport)
        ->and($smallReport)->toBeLessThanOrEqual(3);
});
