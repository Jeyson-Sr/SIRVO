<?php

use App\Modules\Oee\Enums\StopType;

test('a canonical code resolves to its stop type', function (string $code, StopType $expected) {
    expect(StopType::fromLabel($code))->toBe($expected);
})->with([
    ['EQ', StopType::Equipment],
    ['OPD', StopType::Operational],
    ['OR', StopType::Organizational],
    ['PD', StopType::Planned],
    ['QD', StopType::Quality],
    ['RD', StopType::Routine],
    ['TNP', StopType::Unscheduled],
]);

test('the long spanish labels resolve to the same stop type as their code', function (string $label, StopType $expected) {
    expect(StopType::fromLabel($label))->toBe($expected);
})->with([
    ['EQUIPO', StopType::Equipment],
    ['OPERATIVAS', StopType::Operational],
    ['ORGANIZACIONALES', StopType::Organizational],
    ['PLANIFICADAS', StopType::Planned],
    ['PERDIDAS DE CALIDAD', StopType::Quality],
    ['RUTINARIAS', StopType::Routine],
    ['TIEMPO NO PROGRAMADO', StopType::Unscheduled],
]);

test('casing accents and padding do not change the resolved type', function (string $input) {
    expect(StopType::fromLabel($input))->toBe(StopType::Quality);
})->with([
    'accented' => ['PÉRDIDAS DE CALIDAD'],
    'lowercase' => ['pérdidas de calidad'],
    'padded' => ['  calidad  '],
    'singular' => ['CALIDAD'],
]);

test('an unknown or empty label resolves to nothing', function (?string $input) {
    expect(StopType::fromLabel($input))->toBeNull();
})->with([
    'unknown' => ['NO EXISTE'],
    'empty' => [''],
    'whitespace' => ['   '],
    'null' => [null],
]);

test('only unscheduled time is excluded from the loss families', function () {
    expect(StopType::losses())
        ->not->toContain(StopType::Unscheduled)
        ->toHaveCount(count(StopType::cases()) - 1);
});

test('unscheduled time reduces the scheduled window and is not a loss', function () {
    expect(StopType::Unscheduled->reducesScheduledTime())->toBeTrue()
        ->and(StopType::Unscheduled->countsAsLoss())->toBeFalse()
        ->and(StopType::Equipment->reducesScheduledTime())->toBeFalse()
        ->and(StopType::Equipment->countsAsLoss())->toBeTrue();
});

test('every stop type offers a selectable option with a label', function () {
    $options = StopType::options();

    expect($options)->toHaveCount(count(StopType::cases()));

    foreach ($options as $option) {
        expect($option['label'])->not->toBe('');
    }
});

test('admin options name the loss-tree field each family feeds', function () {
    $options = StopType::adminOptions();

    expect($options)->toHaveCount(count(StopType::cases()));

    $opd = collect($options)->firstWhere('value', 'OPD');

    expect($opd['label'])->toContain('OPD')
        ->and($opd['description'])->toContain('OPD');
});
