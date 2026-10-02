<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;

test('guests are redirected to the login screen', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated users are redirected to their team dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard', $user->currentTeam));
});

test('plant members visiting home are sent to the oee panel', function () {
    $user = User::factory()->create();
    $plant = Team::factory()->create();

    $plant->members()->attach($user, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Oee->value],
    ]);
    $user->switchTeam($plant);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('oee.dashboard', $plant));
});
