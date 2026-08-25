<?php

namespace App\Modules\Oee\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Access\OeeAccess;
use App\Modules\Oee\Models\OeeProduction;

class OeeProductionPolicy
{
    /**
     * Determine whether the user can list the team's production runs.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $this->canAccessProductions($user, $team);
    }

    /**
     * Determine whether the user can view the production run.
     */
    public function view(User $user, OeeProduction $production): bool
    {
        return $this->canAccessProductions($user, $production->team);
    }

    /**
     * Determine whether the user can record production for the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $this->canAccessProductions($user, $team)
            && $user->hasTeamPermission($team, TeamPermission::RecordProduction);
    }

    /**
     * Determine whether the user can still amend the production run.
     *
     * Once a shift is signed off its figures are historical record; correcting
     * them requires reopening it first.
     */
    public function update(User $user, OeeProduction $production): bool
    {
        if (! $this->canAccessProductions($user, $production->team)) {
            return false;
        }

        if (! $user->hasTeamPermission($production->team, TeamPermission::RecordProduction)) {
            return false;
        }

        if (! $production->isClosed()) {
            return true;
        }

        return $user->hasTeamPermission($production->team, TeamPermission::ReopenProduction);
    }

    /**
     * Determine whether the user can sign off the production run.
     */
    public function close(User $user, OeeProduction $production): bool
    {
        return $this->update($user, $production);
    }

    /**
     * Determine whether the user can reopen a signed-off production run.
     */
    public function reopen(User $user, OeeProduction $production): bool
    {
        return $production->isClosed()
            && $this->canAccessProductions($user, $production->team)
            && $user->hasTeamPermission($production->team, TeamPermission::ReopenProduction);
    }

    /**
     * Determine whether the user can delete the production run.
     */
    public function delete(User $user, OeeProduction $production): bool
    {
        return $this->canAccessProductions($user, $production->team)
            && $user->hasTeamPermission($production->team, TeamPermission::DeleteProduction);
    }

    /**
     * Recorded shifts are for administrators or operators granted Turnos.
     */
    private function canAccessProductions(User $user, Team $team): bool
    {
        return OeeAccess::viewProductions($user, $team);
    }
}
