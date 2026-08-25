<?php

use App\Modules\Oee\Actions\FillRecordingDefaults;

beforeEach(function () {
    $this->fill = new FillRecordingDefaults;
});

test('the hourly target is copied from pallets when the operator leaves it blank', function () {
    $filled = $this->fill->handle([
        'production' => ['pallets_por_hora' => 28.6],
        'hours' => [[
            'hour_index' => 0,
            'estimado' => null,
            'producido' => 20,
            'stops' => [],
        ]],
    ]);

    expect($filled['hours'][0]['estimado'])->toBe(28.6)
        ->and($filled['hours'][0]['duration_minutes'])->toBe(60.0);
});

test('a shorter slice scales the pallet target to the minutes it ran', function () {
    $filled = $this->fill->handle([
        'production' => ['pallets_por_hora' => 20],
        'hours' => [[
            'hour_index' => 0,
            'duration_minutes' => 30,
            'estimado' => null,
            'stops' => [],
        ]],
    ]);

    expect($filled['hours'][0]['estimado'])->toBe(10.0);
});

test('a stop without minutes takes the unexplained shortfall', function () {
    $filled = $this->fill->handle([
        'production' => [],
        'hours' => [[
            'estimado' => 28.6,
            'producido' => 14.3,
            'stops' => [['codigo' => 'J36']],
        ]],
    ]);

    expect($filled['hours'][0]['stops'][0]['tiempo_minutos'])->toBe(30.0);
});

test('an explicit duration is kept and the next blank stop gets what remains', function () {
    $filled = $this->fill->handle([
        'production' => [],
        'hours' => [[
            'estimado' => 20,
            'producido' => 10,
            'stops' => [
                ['codigo' => 'J38', 'tiempo_minutos' => 10],
                ['codigo' => 'J50'],
            ],
        ]],
    ]);

    expect($filled['hours'][0]['stops'][0]['tiempo_minutos'])->toBe(10)
        ->and($filled['hours'][0]['stops'][1]['tiempo_minutos'])->toBe(20.0);
});

test('meeting the target leaves no minutes to assign', function () {
    $filled = $this->fill->handle([
        'production' => [],
        'hours' => [[
            'estimado' => 28.6,
            'producido' => 28.6,
            'stops' => [['codigo' => 'J36']],
        ]],
    ]);

    expect($filled['hours'][0]['stops'][0]['tiempo_minutos'])->toBe(0.0);
});
