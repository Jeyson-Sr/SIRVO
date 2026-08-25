<?php

use App\Modules\Oee\Enums\StopRanking;

test('every ranking measure offers a selectable option with a label', function () {
    $options = StopRanking::options();

    expect($options)->toHaveCount(count(StopRanking::cases()));

    foreach ($options as $option) {
        expect($option['label'])->not->toBe('');
    }
});

test('the ranking options use the measure value and its spanish label', function () {
    expect(StopRanking::options())->toEqual([
        ['value' => 'minutes', 'label' => 'Minutos perdidos'],
        ['value' => 'frequency', 'label' => 'Frecuencia'],
    ]);
});
