<?php

namespace App\Data;

readonly class TeamPermissions
{
    public function __construct(
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
        public bool $canRecordProduction,
        public bool $canReopenProduction,
        public bool $canDeleteProduction,
        public bool $canManageCatalog,
        public bool $canManageUsers,
        public bool $canViewDashboard,
        public bool $canViewOee,
        public bool $canViewProductions,
        public bool $canViewCatalog,
        public bool $canViewSkus,
    ) {
        //
    }
}
