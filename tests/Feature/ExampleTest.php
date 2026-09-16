<?php

use App\Models\User;

test('guests are redirected to the login screen', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated users are redirected to their team dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard', $user->currentTeam));
});
