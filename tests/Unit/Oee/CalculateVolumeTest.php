<?php

use App\Modules\Oee\Actions\CalculateVolume;

beforeEach(function () {
    $this->volume = new CalculateVolume;
});

test('sporade five hundred millilitres converts pallets into cu', function () {
    // 10 pallets, 100 bottles per pallet, 0.500 L, / 30.
    expect($this->volume->handle(
        palletsProduced: 10.0,
        palletsPerHour: 10.0,
        bph: 1000.0,
        liters: 0.5,
    ))->toBe(16.67);
});

test('a one litre sheet with thirty bottles per pallet leaves cu equal to pallets', function () {
    expect($this->volume->handle(
        palletsProduced: 100.0,
        palletsPerHour: 10.0,
        bph: 300.0,
        liters: 1.0,
    ))->toBe(100.0);
});

test('missing sheet figures produce no volume', function () {
    expect($this->volume->handle(10.0, 0.0, 1000.0, 0.5))->toBe(0.0)
        ->and($this->volume->handle(10.0, 10.0, 0.0, 0.5))->toBe(0.0)
        ->and($this->volume->handle(10.0, 10.0, 1000.0, 0.0))->toBe(0.0);
});
