<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

class GrantSectionAccess
{
    /**
     * Grant or revoke the sections a user may open on the team.
     *
     * Users who are not members yet are added as operators. Owners and
     * administrators keep every section while they hold that rank.
     *
     * @param  array<int, string>|null  $sections
     */
    public function handle(Team $team, User $user, ?array $sections, ?User $actor = null, ?string $role = null): Membership
    {
        $sections = Membership::normalizeOperatorSections($sections);

        $membership = $team->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'role' => TeamRole::Member,
                'sections' => $sections,
            ],
        );

        if ($membership->wasRecentlyCreated) {
            $user->switchTeam($team);
        }

        if ($role !== null && $role !== $membership->role->value) {
            $newRole = TeamRole::from($role);

            abort_unless(
                $actor !== null && $this->canAssign($actor, $user, $team, $membership->role, $newRole),
                403,
            );

            $membership->update(['role' => $newRole]);
            $membership->refresh();
        }

        if ($membership->role === TeamRole::Owner || $membership->role === TeamRole::Admin) {
            return $membership;
        }

        $membership->update(['sections' => $sections]);

        return $membership->refresh();
    }

    /**
     * Determine whether the actor may change the target to the given role.
     */
    public function canAssign(User $actor, User $target, Team $team, TeamRole $currentRole, TeamRole $newRole): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        $actorRole = $actor->teamRole($team);

        return $actorRole !== null
            && $actorRole->outranks($currentRole)
            && $actorRole->outranks($newRole);
    }
}
