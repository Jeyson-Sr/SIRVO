<?php

use App\Modules\Oee\Production\HourStatus;
use App\Modules\Oee\Production\OeeHourDetail;
use App\Modules\Oee\Production\OeeProduction;
use App\Modules\Oee\Production\PresentProduction;
use App\Modules\Oee\Production\PresentRecordingDraft;
use App\Modules\Oee\Production\Shift;

test('the recording draft pads the remaining hours of the shift', function () {
    $production = OeeProduction::factory()->create([
        'turno' => Shift::Day,
        'sku' => '408462',
        'pallets_por_hora' => 18.5,
        'bph' => 24000,
    ]);

    OeeHourDetail::factory()->for($production, 'production')->create([
        'hour_index' => 0,
        'hour_range' => '06:30 - 07:30',
        'producido' => 20,
        'closed' => true,
    ]);

    $draft = (new PresentRecordingDraft)->handle($production);

    expect($draft['hours'])->toHaveCount(12)
        ->and($draft['hours'][0]['producido'])->toBe('20')
        ->and($draft['hours'][1]['hour_range'])->toBe('07:30 - 08:30')
        ->and($draft['hours'][1]['producido'])->toBe('')
        ->and($draft['hours'][1]['sku'])->toBe('408462')
        ->and($draft['production']['sku'])->toBe('408462');
});

test('the detail presenter grades each hour from recorded output', function () {
    $production = OeeProduction::factory()->create();

    OeeHourDetail::factory()->for($production, 'production')->create([
        'hour_index' => 0,
        'estimado' => 20,
        'producido' => 20,
        'closed' => true,
    ]);

    $presented = (new PresentProduction)->handle($production);

    expect($presented['hours'])->toHaveCount(1)
        ->and($presented['hours'][0]['status'])->toBe(HourStatus::OnTarget->value)
        ->and($presented['hours'][0]['statusLabel'])->toBe(HourStatus::OnTarget->label())
        ->and($presented['hours'][0]['producido'])->toBe(20.0);
});
