<?php

namespace App\Modules\Oee\Stops;

use App\Modules\Identity\User;
use App\Modules\Oee\OeeSection;
use App\Modules\Teams\Team;
use App\Modules\Teams\TeamPermission;

class CodStopPolicy
{
    /**
     * Determine whether the user can browse the catalog admin.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                $user->hasTeamPermission($team, TeamPermission::ManageCatalog)
                || $user->hasSection($team, OeeSection::Catalog)
            );
    }

    /**
     * Determine whether the user can add a catalog entry.
     */
    public function create(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    /**
     * Determine whether the user can change a catalog entry.
     */
    public function update(User $user, CodStop $codStop, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    /**
     * Determine whether the user can remove a catalog entry.
     */
    public function delete(User $user, CodStop $codStop, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    /**
     * Editing the catalog stays with plant administrators.
     */
    private function canManage(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && $user->hasTeamPermission($team, TeamPermission::ManageCatalog);
    }
}
