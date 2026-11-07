<?php

namespace Database\Seeders;

use App\Modules\Identity\User;
use App\Modules\Platform\AppSection;
use App\Modules\Teams\Team;
use App\Modules\Teams\TeamRole;
use Illuminate\Database\Seeder;

class PlantAdminSeeder extends Seeder
{
    public const EMAIL = 'admin.caral.pe@ecaral.pe';

    public const PASSWORD = 'admin';

    /**
     * Create the plant administrator who manages every account.
     */
    public function run(): void
    {
        $plant = Team::query()->firstOrCreate(
            ['slug' => 'planta-lima'],
            [
                'name' => 'Planta Lima',
                'is_personal' => false,
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Admin Caral',
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'current_team_id' => $plant->id,
            ],
        );

        $plant->memberships()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => TeamRole::Admin,
                'sections' => AppSection::values(),
            ],
        );

        $user->switchTeam($plant);
    }
}
