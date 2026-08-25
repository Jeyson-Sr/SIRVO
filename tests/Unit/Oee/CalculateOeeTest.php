<?php

use App\Modules\Oee\Actions\CalculateOee;
use App\Modules\Oee\Enums\StopType;

beforeEach(function () {
    $this->calculator = new CalculateOee;
});

test('an hour with no downtime is fully productive', function () {
    $metrics = $this->calculator->handle(closedHours: 8, downtimeMinutesByType: []);

    expect($metrics->scheduledMinutes)->toBe(480.0)
        ->and($metrics->effectiveMinutes)->toBe(480.0)
        ->and($metrics->productiveMinutes)->toBe(480.0)
        ->and($metrics->oee)->toBe(100.0)
        ->and($metrics->em)->toBe(100.0);
});

test('a product-change slice schedules only the minutes it ran', function () {
    $metrics = $this->calculator->handle(
        closedHours: 2,
        downtimeMinutesByType: [],
        scheduledMinutes: 90.0,
    );

    expect($metrics->scheduledMinutes)->toBe(90.0)
        ->and($metrics->effectiveMinutes)->toBe(90.0)
        ->and($metrics->oee)->toBe(100.0);
});

test('no closed hours produces empty metrics instead of dividing by zero', function () {
    $metrics = $this->calculator->handle(closedHours: 0, downtimeMinutesByType: [
        StopType::Equipment->value => 30.0,
    ]);

    expect($metrics->oee)->toBe(0.0)
        ->and($metrics->em)->toBe(0.0)
        ->and($metrics->effectiveMinutes)->toBe(0.0);
});

test('unscheduled time shrinks the measured window instead of counting as a loss', function () {
    // Eight hours scheduled, one of them never planned to run, nothing else lost.
    $metrics = $this->calculator->handle(closedHours: 8, downtimeMinutesByType: [
        StopType::Unscheduled->value => 60.0,
    ]);

    expect($metrics->scheduledMinutes)->toBe(480.0)
        ->and($metrics->unscheduledMinutes)->toBe(60.0)
        ->and($metrics->effectiveMinutes)->toBe(420.0)
        ->and($metrics->productiveMinutes)->toBe(420.0)
        ->and($metrics->oee)->toBe(100.0);
});

test('equipment downtime lowers both oee and em', function () {
    $metrics = $this->calculator->handle(closedHours: 8, downtimeMinutesByType: [
        StopType::Equipment->value => 60.0,
    ]);

    // 420 productive minutes out of 480 effective.
    expect($metrics->oee)->toBe(87.5)
        ->and($metrics->em)->toBe(87.5);
});

test('non equipment downtime lowers oee but leaves em untouched', function () {
    $metrics = $this->calculator->handle(closedHours: 8, downtimeMinutesByType: [
        StopType::Operational->value => 60.0,
    ]);

    expect($metrics->oee)->toBe(87.5)
        ->and($metrics->em)->toBe(100.0);
});

test('every loss family is charged against effective time', function () {
    $metrics = $this->calculator->handle(closedHours: 10, downtimeMinutesByType: [
        StopType::Unscheduled->value => 120.0,
        StopType::Equipment->value => 48.0,
        StopType::Operational->value => 24.0,
        StopType::Organizational->value => 12.0,
        StopType::Planned->value => 36.0,
        StopType::Quality->value => 6.0,
        StopType::Routine->value => 18.0,
    ]);

    // 600 scheduled - 120 unscheduled = 480 effective; 144 lost leaves 336 productive.
    expect($metrics->effectiveMinutes)->toBe(480.0)
        ->and($metrics->productiveMinutes)->toBe(336.0)
        ->and($metrics->oee)->toBe(70.0)
        ->and($metrics->em)->toBe(90.0);

    expect($metrics->impactFor(StopType::Equipment))->toBe(10.0)
        ->and($metrics->impactFor(StopType::Operational))->toBe(5.0)
        ->and($metrics->impactFor(StopType::Organizational))->toBe(2.5)
        ->and($metrics->impactFor(StopType::Planned))->toBe(7.5)
        ->and($metrics->impactFor(StopType::Quality))->toBe(1.25)
        ->and($metrics->impactFor(StopType::Routine))->toBe(3.75);
});

test('quality losses are reported and not silently dropped', function () {
    $metrics = $this->calculator->handle(closedHours: 1, downtimeMinutesByType: [
        StopType::Quality->value => 15.0,
    ]);

    expect($metrics->minutesFor(StopType::Quality))->toBe(15.0)
        ->and($metrics->impactFor(StopType::Quality))->toBe(25.0)
        ->and($metrics->oee)->toBe(75.0);
});

test('losses larger than the effective window floor oee at zero', function () {
    $metrics = $this->calculator->handle(closedHours: 1, downtimeMinutesByType: [
        StopType::Equipment->value => 90.0,
    ]);

    expect($metrics->productiveMinutes)->toBe(0.0)
        ->and($metrics->oee)->toBe(0.0)
        ->and($metrics->em)->toBe(0.0);
});

test('unscheduled time cannot exceed the scheduled window', function () {
    $metrics = $this->calculator->handle(closedHours: 2, downtimeMinutesByType: [
        StopType::Unscheduled->value => 500.0,
    ]);

    expect($metrics->unscheduledMinutes)->toBe(120.0)
        ->and($metrics->effectiveMinutes)->toBe(0.0)
        ->and($metrics->oee)->toBe(0.0);
});

test('negative downtime is discarded rather than inflating oee', function () {
    $metrics = $this->calculator->handle(closedHours: 1, downtimeMinutesByType: [
        StopType::Equipment->value => -30.0,
    ]);

    expect($metrics->minutesFor(StopType::Equipment))->toBe(0.0)
        ->and($metrics->oee)->toBe(100.0);
});

test('unrecognised keys in the downtime map are ignored', function () {
    $metrics = $this->calculator->handle(closedHours: 1, downtimeMinutesByType: [
        'NOT_A_TYPE' => 30.0,
    ]);

    expect($metrics->oee)->toBe(100.0)
        ->and($metrics->lossMinutes)->not->toHaveKey('NOT_A_TYPE');
});

test('every stop type is always present in the breakdown', function () {
    $metrics = $this->calculator->handle(closedHours: 4, downtimeMinutesByType: []);

    foreach (StopType::cases() as $stopType) {
        expect($metrics->lossMinutes)->toHaveKey($stopType->value)
            ->and($metrics->lossImpact)->toHaveKey($stopType->value);
    }
});
