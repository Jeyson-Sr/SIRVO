<?php

use App\Modules\Identity\User;
use App\Modules\Teams\Team;
use App\Modules\Teams\TeamRole;
use Database\Seeders\PlantAdminSeeder;
use Illuminate\Support\Facades\Hash;

test('the plant admin seeder creates the caral administrator', function () {
    $this->seed(PlantAdminSeeder::class);

    $user = User::query()->where('email', PlantAdminSeeder::EMAIL)->first();
    $plant = Team::query()->where('slug', 'planta-lima')->first();

    expect($user)->not->toBeNull()
        ->and($plant)->not->toBeNull()
        ->and($user->teamRole($plant))->toBe(TeamRole::Admin)
        ->and(Hash::check(PlantAdminSeeder::PASSWORD, $user->password))->toBeTrue();
});
