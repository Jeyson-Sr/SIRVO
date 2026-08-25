<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;

test('plant roles expose spanish labels', function () {
    expect(TeamRole::Owner->label())->toBe('Propietario')
        ->and(TeamRole::Admin->label())->toBe('Administrador')
        ->and(TeamRole::Engineer->label())->toBe('Ingeniero')
        ->and(TeamRole::Member->label())->toBe('Operador')
        ->and(TeamRole::Viewer->label())->toBe('Visor');
});

test('only owners and admins may manage the stop catalog and users', function () {
    expect(TeamRole::Owner->hasPermission(TeamPermission::ManageCatalog))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageCatalog))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageUsers))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteProduction))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::AddMember))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::UpdateMember))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::RemoveMember))->toBeTrue()
        ->and(TeamRole::Engineer->hasPermission(TeamPermission::ManageCatalog))->toBeFalse()
        ->and(TeamRole::Member->hasPermission(TeamPermission::ManageUsers))->toBeFalse();
});

test('an actor may only assign roles below their own rank', function () {
    expect(TeamRole::assignableBy(TeamRole::Admin))->toEqual([
        ['value' => 'engineer', 'label' => 'Ingeniero'],
        ['value' => 'member', 'label' => 'Operador'],
        ['value' => 'viewer', 'label' => 'Visor'],
    ])->and(TeamRole::Admin->outranks(TeamRole::Member))->toBeTrue()
        ->and(TeamRole::Admin->outranks(TeamRole::Admin))->toBeFalse();
});

test('engineers may record and reopen production but not invite members', function () {
    expect(TeamRole::Engineer->hasPermission(TeamPermission::RecordProduction))->toBeTrue()
        ->and(TeamRole::Engineer->hasPermission(TeamPermission::ReopenProduction))->toBeTrue()
        ->and(TeamRole::Engineer->hasPermission(TeamPermission::CreateInvitation))->toBeFalse();
});

test('operators may record production and nothing else', function () {
    expect(TeamRole::Member->permissions())->toBe([TeamPermission::RecordProduction]);
});

test('viewers have no write permissions', function () {
    expect(TeamRole::Viewer->permissions())->toBe([])
        ->and(TeamRole::Viewer->hasPermission(TeamPermission::RecordProduction))->toBeFalse();
});

test('only administrators and owners manage the plant', function () {
    expect(TeamRole::Owner->managesPlant())->toBeTrue()
        ->and(TeamRole::Admin->managesPlant())->toBeTrue()
        ->and(TeamRole::Engineer->managesPlant())->toBeFalse()
        ->and(TeamRole::Member->managesPlant())->toBeFalse();
});

test('assignable roles exclude the owner', function () {
    expect(TeamRole::assignable())->toEqual([
        ['value' => 'admin', 'label' => 'Administrador'],
        ['value' => 'engineer', 'label' => 'Ingeniero'],
        ['value' => 'member', 'label' => 'Operador'],
        ['value' => 'viewer', 'label' => 'Visor'],
    ]);
});
