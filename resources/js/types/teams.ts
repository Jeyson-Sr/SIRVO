export type TeamRole = 'owner' | 'admin' | 'engineer' | 'member' | 'viewer';

export type Team = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: TeamRole;
    roleLabel?: string;
    isCurrent?: boolean;
};

export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamInvitationContext = {
    code: string;
    teamName: string;
};

export type DashboardInvitation = {
    code: string;
    inviterName: string;
    team: {
        name: string;
        slug: string;
    };
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
    canRecordProduction: boolean;
    canReopenProduction: boolean;
    canDeleteProduction: boolean;
    canManageCatalog: boolean;
    canManageUsers: boolean;
    canViewDashboard: boolean;
    canViewOee: boolean;
    canViewProductions: boolean;
    canViewCatalog: boolean;
    canViewSkus: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};

export type AppSectionValue =
    'dashboard' | 'oee' | 'productions' | 'catalog' | 'skus';

export type TeamAccessUser = {
    id: number;
    name: string;
    email: string;
    isMember: boolean;
    role: string;
    roleLabel: string;
    sections: AppSectionValue[];
    locked: boolean;
    canDelete: boolean;
    canChangeRole: boolean;
};
