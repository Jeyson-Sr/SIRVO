<?php

use App\Data\SectionCatalog;
use App\Enums\AppSection;
use Tests\TestCase;

uses(TestCase::class);

test('dashboard is the only platform section', function () {
    expect(AppSection::cases())->toBe([AppSection::Dashboard])
        ->and(AppSection::Dashboard->label())->toBe('Dashboard')
        ->and(AppSection::grantable())->toBe([AppSection::Dashboard]);
});

test('registered modules still appear among grantable plant sections', function () {
    expect(AppSection::values())->toBe(['dashboard', 'oee', 'productions', 'catalog', 'skus'])
        ->and(AppSection::grantableValues())->toBe([
            'dashboard',
            'oee',
            'productions',
            'catalog',
            'skus',
        ])
        ->and(AppSection::operatorDefaults())->toBe(['oee'])
        ->and(AppSection::grantableOptions())->toBe(SectionCatalog::grantableOptions());
});
