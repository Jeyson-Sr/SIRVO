<?php

namespace App\Actions\Teams;

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class JoinPlantTeam
{
    /**
     * Add the user to Planta Lima as an operator with Panel OEE.
     */
    public function handle(User $user): ?Team
    {
        $plant = Team::query()->where('slug', 'planta-lima')->first();

        if (! $plant instanceof Team) {
            return null;
        }

        $plant->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'role' => TeamRole::Member,
                'sections' => AppSection::operatorDefaults(),
            ],
        );

        $user->switchTeam($plant);

        return $plant;
    }
}
