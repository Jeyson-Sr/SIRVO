<?php

use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopType;

test('a day selection resolves to that single date', function () {
    $filters = ProductionFilters::fromArray(['day' => '2026-03-15']);

    expect($filters->from?->toDateString())->toBe('2026-03-15')
        ->and($filters->to?->toDateString())->toBe('2026-03-15');
});

test('an iso week selection resolves to its monday and sunday', function () {
    // Week 11 of 2026 runs from Monday the 9th to Sunday the 15th of March.
    $filters = ProductionFilters::fromArray(['year' => 2026, 'week' => 11]);

    expect($filters->from?->toDateString())->toBe('2026-03-09')
        ->and($filters->to?->toDateString())->toBe('2026-03-15');
});

test('a month selection resolves to the whole month', function () {
    $filters = ProductionFilters::fromArray(['year' => 2026, 'month' => 2]);

    expect($filters->from?->toDateString())->toBe('2026-02-01')
        ->and($filters->to?->toDateString())->toBe('2026-02-28');
});

test('a year selection resolves to the whole year', function () {
    $filters = ProductionFilters::fromArray(['year' => 2026]);

    expect($filters->from?->toDateString())->toBe('2026-01-01')
        ->and($filters->to?->toDateString())->toBe('2026-12-31');
});

test('an explicit range wins over the calendar shortcuts', function () {
    $filters = ProductionFilters::fromArray([
        'from' => '2026-01-05',
        'to' => '2026-01-09',
        'day' => '2026-03-15',
        'year' => 2020,
    ]);

    expect($filters->from?->toDateString())->toBe('2026-01-05')
        ->and($filters->to?->toDateString())->toBe('2026-01-09');
});

test('a day selection wins over a week or month selection', function () {
    $filters = ProductionFilters::fromArray([
        'day' => '2026-03-15',
        'week' => 2,
        'month' => 7,
    ]);

    expect($filters->from?->toDateString())->toBe('2026-03-15')
        ->and($filters->to?->toDateString())->toBe('2026-03-15');
});

test('no calendar selection leaves the period open', function () {
    $filters = ProductionFilters::fromArray([]);

    expect($filters->hasDateRange())->toBeFalse()
        ->and($filters->from)->toBeNull()
        ->and($filters->to)->toBeNull();
});

test('blank text filters are treated as absent', function () {
    $filters = ProductionFilters::fromArray(['linea' => '  ', 'marca' => '']);

    expect($filters->linea)->toBeNull()
        ->and($filters->marca)->toBeNull();
});

test('text filters are trimmed', function () {
    $filters = ProductionFilters::fromArray(['linea' => ' L4 ']);

    expect($filters->linea)->toBe('L4');
});

test('the component filter resolves to a stop type', function () {
    $filters = ProductionFilters::fromArray(['componente' => 'EQUIPO']);

    expect($filters->componente)->toBe(StopType::Equipment);
});

test('reports cover only closed hours unless told otherwise', function () {
    expect(ProductionFilters::fromArray([])->closedOnly)->toBeTrue()
        ->and(ProductionFilters::fromArray(['closed_only' => false])->closedOnly)->toBeFalse();
});
