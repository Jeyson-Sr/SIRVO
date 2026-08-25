<?php

use App\Modules\Oee\Enums\OeeSection;

test('oee sections keep the stored membership values and spanish labels', function () {
    expect(OeeSection::Oee->value)->toBe('oee')
        ->and(OeeSection::Productions->value)->toBe('productions')
        ->and(OeeSection::Catalog->value)->toBe('catalog')
        ->and(OeeSection::Skus->value)->toBe('skus')
        ->and(OeeSection::Oee->label())->toBe('Panel OEE')
        ->and(OeeSection::Productions->label())->toBe('Turnos')
        ->and(OeeSection::Catalog->label())->toBe('Códigos de parada')
        ->and(OeeSection::Skus->label())->toBe('Productos');
});

test('a new operator starts with panel oee only', function () {
    expect(OeeSection::operatorDefaults())->toBe(['oee']);
});
