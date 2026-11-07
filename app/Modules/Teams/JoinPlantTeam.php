<?php

namespace App\Modules\Teams;

use App\Modules\Identity\User;
use App\Modules\Platform\AppSection;

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
