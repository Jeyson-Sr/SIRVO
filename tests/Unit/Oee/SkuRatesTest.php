<?php

use App\Modules\Oee\Support\SkuRates;
use Tests\TestCase;

uses(TestCase::class);

test('paq pallet is packs on a layer times the number of layers', function () {
    expect(SkuRates::paqPallet(20, 7))->toBe(140)
        ->and(SkuRates::paqPallet(25, 8))->toBe(200)
        ->and(SkuRates::paqPallet(0, 7))->toBe(0);
});

test('pallets per hour divide bottles by pack size and then by packs on the pallet', function () {
    expect(SkuRates::palletsPerHour(60000, 15, 140))->toBe(28.57)
        ->and(SkuRates::palletsPerHour(55000, 15, 200))->toBe(18.33)
        ->and(SkuRates::palletsPerHour(60000, 0, 140))->toBe(0.0);
});

test('rates can be derived from the packaging fields of a catalog row', function () {
    expect(SkuRates::fromInput([
        'bph' => 60000,
        'um' => 15,
        'paq_cama' => 20,
        'nivel' => 7,
    ]))->toBe([
        'paq_pallet' => 140,
        'pallets_por_hora' => 28.57,
    ]);
});

test('the plant catalog has one row per sku and line', function () {
    /** @var array<int, array{sku: string, linea: string}> $catalog */
    $catalog = require database_path('data/oee_skus.php');

    $pairs = collect($catalog)->map(fn (array $row): string => $row['sku'].'|'.$row['linea']);

    expect($catalog)->toHaveCount(231)
        ->and($pairs->unique()->count())->toBe(231);
});
