<?php

use App\Modules\Oee\Actions\SplitHourAt;

beforeEach(function () {
    $this->split = new SplitHourAt;
});

test('a midday change splits the hour and scales each sku target', function () {
    $parts = $this->split->handle('11:30 - 12:30', '12:00', 20.0, 28.6);

    expect($parts['before']['hour_range'])->toBe('11:30 - 12:00')
        ->and($parts['before']['duration_minutes'])->toBe(30.0)
        ->and($parts['before']['estimado'])->toBe(10.0)
        ->and($parts['after']['hour_range'])->toBe('12:00 - 12:30')
        ->and($parts['after']['duration_minutes'])->toBe(30.0)
        ->and($parts['after']['estimado'])->toBe(14.3);
});

test('a cut near the end of the slot leaves a short remainder', function () {
    $parts = $this->split->handle('06:30 - 07:30', '07:15', 18.0, 18.0);

    expect($parts['before']['duration_minutes'])->toBe(45.0)
        ->and($parts['before']['estimado'])->toBe(13.5)
        ->and($parts['after']['duration_minutes'])->toBe(15.0)
        ->and($parts['after']['estimado'])->toBe(4.5);
});

test('a cut on the slot boundary is rejected', function () {
    $this->split->handle('11:30 - 12:30', '11:30', 20.0, 20.0);
})->throws(InvalidArgumentException::class);
