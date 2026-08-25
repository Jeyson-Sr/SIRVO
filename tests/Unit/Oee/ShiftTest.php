<?php

use App\Modules\Oee\Enums\Shift;

test('a shift covers twelve consecutive hour slots', function (Shift $shift) {
    expect($shift->hourRanges())->toHaveCount(Shift::HOURS_PER_SHIFT);
})->with([
    'day' => [Shift::Day],
    'night' => [Shift::Night],
]);

test('the day shift starts at half past six in the morning', function () {
    $ranges = Shift::Day->hourRanges();

    expect($ranges[0])->toBe('06:30 - 07:30')
        ->and($ranges[11])->toBe('17:30 - 18:30');
});

test('the night shift wraps past midnight', function () {
    $ranges = Shift::Night->hourRanges();

    expect($ranges[0])->toBe('18:30 - 19:30')
        ->and($ranges[5])->toBe('23:30 - 00:30')
        ->and($ranges[11])->toBe('05:30 - 06:30');
});

test('each slot starts where the previous one ended', function (Shift $shift) {
    $ranges = $shift->hourRanges();

    for ($slot = 1; $slot < count($ranges); $slot++) {
        [, $previousEnd] = explode(' - ', $ranges[$slot - 1]);
        [$currentStart] = explode(' - ', $ranges[$slot]);

        expect($currentStart)->toBe($previousEnd);
    }
})->with([
    'day' => [Shift::Day],
    'night' => [Shift::Night],
]);

test('the two shifts together cover a full day without overlapping', function () {
    $allSlots = [...Shift::Day->hourRanges(), ...Shift::Night->hourRanges()];

    expect(array_unique($allSlots))->toHaveCount(24);
});
