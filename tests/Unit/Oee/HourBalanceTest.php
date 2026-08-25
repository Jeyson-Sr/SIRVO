<?php

use App\Modules\Oee\Models\OeeHourDetail;

test('meeting the target is balanced without stops', function () {
    expect(OeeHourDetail::isBalanced(estimated: 20.0, produced: 20.0, justifiedMinutes: 0.0))->toBeTrue();
});

test('a shortfall is not balanced until the minutes are explained', function () {
    expect(OeeHourDetail::isBalanced(estimated: 20.0, produced: 10.0, justifiedMinutes: 0.0))->toBeFalse()
        ->and(OeeHourDetail::isBalanced(estimated: 20.0, produced: 10.0, justifiedMinutes: 30.0))->toBeTrue();
});

test('an hour without produced output cannot unlock the next slot', function () {
    expect(OeeHourDetail::isBalanced(estimated: 20.0, produced: null, justifiedMinutes: 0.0))->toBeFalse();
});

test('stops cannot exceed the sixty minutes of the hour', function () {
    expect(OeeHourDetail::isBalanced(estimated: 20.0, produced: 0.0, justifiedMinutes: 60.01))->toBeFalse();
});

test('a leftover that still reads as zero minutes is treated as squared', function () {
    $shortfall = OeeHourDetail::minutesToJustify(7.0, 0.5);

    expect(OeeHourDetail::isBalanced(7.0, 0.5, $shortfall - 0.05))->toBeTrue();
});

test('a product-change slice justifies against its own minutes not a full hour', function () {
    expect(OeeHourDetail::minutesToJustify(10.0, 5.0, 30.0))->toBe(15.0)
        ->and(OeeHourDetail::isBalanced(10.0, 5.0, 15.0, 30.0))->toBeTrue()
        ->and(OeeHourDetail::isBalanced(10.0, 5.0, 31.0, 30.0))->toBeFalse();
});

test('the shortfall minutes match the production gap', function () {
    expect(OeeHourDetail::minutesToJustify(28.6, 14.3))->toBe(30.0)
        ->and(OeeHourDetail::minutesToJustify(20.0, 20.0))->toBe(0.0)
        ->and(OeeHourDetail::minutesToJustify(20.0, null))->toBe(0.0);
});
