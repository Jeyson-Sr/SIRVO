<?php

namespace App\Actions\Teams;

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeamUser
{
    /**
     * Create a user in the users table and add them to the team.
     *
     * @param  array{name: string, email: string, password: string, sections?: array<int, string>|null}  $data
     */
    public function handle(Team $team, array $data): User
    {
        return DB::transaction(function () use ($team, $data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'current_team_id' => $team->id,
            ]);

            $user->markEmailAsVerified();

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Member,
                'sections' => Membership::normalizeOperatorSections(
                    $data['sections'] ?? AppSection::operatorDefaults(),
                ),
            ]);

            return $user;
        });
    }
}
