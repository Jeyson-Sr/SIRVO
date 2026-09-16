<?php

use App\Data\SectionCatalog;
use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('the catalog merges dashboard with registered module sections', function () {
    expect(SectionCatalog::values())->toBe(['dashboard', 'oee', 'paradas', 'productions', 'catalog', 'skus'])
        ->and(SectionCatalog::grantableValues())->toBe(SectionCatalog::values())
        ->and(SectionCatalog::operatorDefaults())->toBe(['oee']);
});

test('grantable options keep dashboard then the oee screens', function () {
    expect(SectionCatalog::grantableOptions())->toBe([
        ['value' => AppSection::Dashboard->value, 'label' => 'Dashboard'],
        ['value' => OeeSection::Oee->value, 'label' => 'Panel OEE'],
        ['value' => OeeSection::Paradas->value, 'label' => 'Paradas'],
        ['value' => OeeSection::Productions->value, 'label' => 'Turnos'],
        ['value' => OeeSection::Catalog->value, 'label' => 'Códigos de parada'],
        ['value' => OeeSection::Skus->value, 'label' => 'Productos'],
    ]);
});

test('oee permissions follow plant rank and granted sections', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $operator = User::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($operator, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Oee->value],
    ]);

    $adminFlags = SectionCatalog::permissions($admin, $team);
    $operatorFlags = SectionCatalog::permissions($operator, $team);

    expect($adminFlags['canViewOee'])->toBeTrue()
        ->and($adminFlags['canViewParadas'])->toBeTrue()
        ->and($adminFlags['canViewProductions'])->toBeTrue()
        ->and($adminFlags['canViewCatalog'])->toBeTrue()
        ->and($adminFlags['canViewSkus'])->toBeTrue()
        ->and($adminFlags['canManageCatalog'])->toBeTrue()
        ->and($operatorFlags['canViewOee'])->toBeTrue()
        ->and($operatorFlags['canViewParadas'])->toBeFalse()
        ->and($operatorFlags['canViewProductions'])->toBeFalse()
        ->and($operatorFlags['canViewCatalog'])->toBeFalse()
        ->and($operatorFlags['canViewSkus'])->toBeFalse()
        ->and($operatorFlags['canManageCatalog'])->toBeFalse();
});
