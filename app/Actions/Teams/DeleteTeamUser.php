<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class DeleteTeamUser
{
    /**
     * Remove a team member from the users table.
     *
     * The actor must outrank the target. The signed-in user cannot
     * delete their own account from this screen.
     */
    public function handle(Team $team, User $actor, User $target): void
    {
        $membership = $target->membershipOn($team);

        if ($membership === null) {
            abort_if($actor->is($target) || $actor->teamRole($team) === null, 403);

            $target->delete();

            return;
        }

        if (! $this->authorized($actor, $target, $team, $membership->role)) {
            abort(403);
        }

        $target->delete();
    }

    /**
     * Determine whether the actor may delete the target on this team.
     */
    public function authorized(User $actor, User $target, Team $team, TeamRole $targetRole, ?TeamRole $actorRole = null): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        $actorRole ??= $actor->teamRole($team);

        return $actorRole !== null && $actorRole->outranks($targetRole);
    }
}
