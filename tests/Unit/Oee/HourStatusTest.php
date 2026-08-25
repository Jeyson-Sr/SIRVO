<?php

use App\Modules\Oee\Enums\HourStatus;

test('meeting or beating the target is on target', function (float $produced) {
    expect(HourStatus::fromOutput(estimated: 20.0, produced: $produced))->toBe(HourStatus::OnTarget);
})->with([
    'exactly on target' => [20.0],
    'above target' => [25.0],
]);

test('between eighty percent and the target is a warning', function (float $produced) {
    expect(HourStatus::fromOutput(estimated: 20.0, produced: $produced))->toBe(HourStatus::Warning);
})->with([
    'at the threshold' => [16.0],
    'just under target' => [19.9],
]);

test('below eighty percent is critical', function () {
    expect(HourStatus::fromOutput(estimated: 20.0, produced: 15.9))->toBe(HourStatus::Critical);
});

test('a recorded zero is critical rather than pending', function () {
    expect(HourStatus::fromOutput(estimated: 20.0, produced: 0.0))->toBe(HourStatus::Critical);
});

test('an hour with nothing recorded yet is pending', function () {
    expect(HourStatus::fromOutput(estimated: 20.0, produced: null))->toBe(HourStatus::Pending);
});

test('an hour without a target cannot be judged', function () {
    expect(HourStatus::fromOutput(estimated: 0.0, produced: 10.0))->toBe(HourStatus::Pending);
});

test('every status carries a label', function () {
    foreach (HourStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});
