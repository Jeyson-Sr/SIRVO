<?php

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Modules\Oee\Enums\OeeSection;
use Tests\TestCase;

uses(TestCase::class);

test('operators keep only the view sections that were granted', function () {
    $membership = new Membership([
        'role' => TeamRole::Member,
        'sections' => [AppSection::Dashboard->value, OeeSection::Productions->value],
    ]);

    expect($membership->grantedSections())->toBe([
        AppSection::Dashboard->value,
        OeeSection::Productions->value,
    ]);
});

test('panel oee can be revoked from an operator', function () {
    expect(Membership::normalizeOperatorSections([]))->toBe([])
        ->and(Membership::normalizeOperatorSections([OeeSection::Oee->value]))
        ->toBe([OeeSection::Oee->value]);
});

test('a membership without stored sections starts with panel oee', function () {
    $membership = new Membership([
        'role' => TeamRole::Member,
        'sections' => null,
    ]);

    expect($membership->grantedSections())->toBe([OeeSection::Oee->value]);
});

test('administrators keep every section', function () {
    $membership = new Membership([
        'role' => TeamRole::Admin,
        'sections' => [],
    ]);

    expect($membership->grantedSections())->toBe(AppSection::values());
});
