<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Engineer = 'engineer';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Admin => 'Administrador',
            self::Engineer => 'Ingeniero',
            self::Member => 'Operador',
            self::Viewer => 'Visor',
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::AddMember,
                TeamPermission::UpdateMember,
                TeamPermission::RemoveMember,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::RecordProduction,
                TeamPermission::ReopenProduction,
                TeamPermission::DeleteProduction,
                TeamPermission::ManageCatalog,
                TeamPermission::ManageUsers,
            ],
            self::Engineer => [
                TeamPermission::RecordProduction,
                TeamPermission::ReopenProduction,
            ],
            self::Member => [
                TeamPermission::RecordProduction,
            ],
            self::Viewer => [],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 4,
            self::Admin => 3,
            self::Engineer => 2,
            self::Member => 1,
            self::Viewer => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Administrators and owners manage the plant: turnos, catalog, and users.
     */
    public function managesPlant(): bool
    {
        return $this->isAtLeast(self::Admin);
    }

    /**
     * Determine whether this role outranks another.
     */
    public function outranks(self $role): bool
    {
        return $this->level() > $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return self::assignableBy(self::Owner);
    }

    /**
     * Get the roles the actor may assign: strictly below their own rank.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignableBy(?self $actor): array
    {
        if ($actor === null) {
            return [];
        }

        return collect(self::cases())
            ->filter(fn (self $role) => $actor->outranks($role))
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->all();
    }
}
