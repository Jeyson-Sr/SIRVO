<?php

use App\Modules\Oee\Data\ProductionOrder;

test('a short order is padded to six digits and prefixed with its year', function () {
    expect(ProductionOrder::fromInput('424', '2026-03-15')->value)->toBe('2026000424');
});

test('an already padded order is left alone', function () {
    expect(ProductionOrder::fromInput('000424', '2026-03-15')->value)->toBe('2026000424');
});

test('an order that already carries a year keeps only its last six digits', function () {
    expect(ProductionOrder::fromInput('2025000424', '2026-03-15')->value)->toBe('2026000424');
});

test('non numeric characters are stripped before normalising', function () {
    expect(ProductionOrder::fromInput('OP-424/A', '2026-03-15')->value)->toBe('2026000424');
});

test('the year comes from the production date and not from today', function () {
    expect(ProductionOrder::fromInput('424', '2019-12-31')->value)->toBe('2019000424');
});

test('every spelling of the same order collapses to one value', function () {
    $spellings = ['424', '000424', 'OP424', '2020000424', ' 424 '];

    $normalised = array_map(
        fn (string $spelling) => ProductionOrder::fromInput($spelling, '2026-03-15')->value,
        $spellings,
    );

    expect(array_unique($normalised))->toHaveCount(1);
});

test('an order without digits is rejected', function () {
    ProductionOrder::fromInput('sin-numero', '2026-03-15');
})->throws(InvalidArgumentException::class);

test('an order can be cast to its string value', function () {
    expect((string) ProductionOrder::fromInput('424', '2026-03-15'))->toBe('2026000424');
});
