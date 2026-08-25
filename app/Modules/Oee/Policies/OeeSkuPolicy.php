<?php

namespace App\Modules\Oee\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use App\Modules\Oee\Models\OeeSku;

class OeeSkuPolicy
{
    /**
     * Determine whether the user can browse the finished-goods catalog.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                $user->hasTeamPermission($team, TeamPermission::ManageCatalog)
                || $user->hasSection($team, OeeSection::Skus)
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
    public function update(User $user, OeeSku $oeeSku, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    /**
     * Determine whether the user can remove a catalog entry.
     */
    public function delete(User $user, OeeSku $oeeSku, Team $team): bool
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
