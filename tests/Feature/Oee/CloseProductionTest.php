<?php

use App\Modules\Oee\Actions\CloseProduction;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use Illuminate\Validation\ValidationException;

test('a shift cannot close while any hour is still open', function () {
    $production = OeeProduction::factory()->create();

    OeeHourDetail::factory()->for($production, 'production')->open()->create();

    expect(fn () => (new CloseProduction)->handle($production))
        ->toThrow(ValidationException::class);

    expect($production->fresh()->isClosed())->toBeFalse();
});

test('a shift closes once every hour is signed off', function () {
    $production = OeeProduction::factory()->create();

    OeeHourDetail::factory()->for($production, 'production')->create([
        'closed' => true,
    ]);

    (new CloseProduction)->handle($production);

    expect($production->fresh()->isClosed())->toBeTrue();
});
