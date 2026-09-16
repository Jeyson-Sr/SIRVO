<?php

namespace App\Modules\Oee\Access;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use Illuminate\Http\RedirectResponse;

class OeeAccess
{
    /**
     * Determine whether the user may open the OEE panel.
     */
    public static function viewPanel(User $user, Team $team): bool
    {
        return ! $team->is_personal && $user->hasSection($team, OeeSection::Oee);
    }

    /**
     * Determine whether the user may open the stop-comment report.
     */
    public static function viewParadas(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                ($user->teamRole($team)?->managesPlant() ?? false)
                || $user->hasSection($team, OeeSection::Paradas)
            );
    }

    /**
     * Determine whether the user may open recorded shifts.
     */
    public static function viewProductions(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                ($user->teamRole($team)?->managesPlant() ?? false)
                || $user->hasSection($team, OeeSection::Productions)
            );
    }

    /**
     * Determine whether the user may open the stop-code catalog.
     */
    public static function viewCatalog(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                ($user->teamRole($team)?->managesPlant() ?? false)
                || $user->hasSection($team, OeeSection::Catalog)
            );
    }

    /**
     * Determine whether the user may open the finished-goods catalog.
     */
    public static function viewSkus(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && (
                ($user->teamRole($team)?->managesPlant() ?? false)
                || $user->hasSection($team, OeeSection::Skus)
            );
    }

    /**
     * Deny access unless the user may open the OEE panel.
     */
    public static function ensurePanel(?User $user, Team $team): void
    {
        abort_unless($user !== null && self::viewPanel($user, $team), 403);
    }

    /**
     * Deny access unless the user may open the stop-comment report.
     */
    public static function ensureParadas(?User $user, Team $team): void
    {
        abort_unless($user !== null && self::viewParadas($user, $team), 403);
    }

    /**
     * Deny access unless the user may open recorded shifts.
     */
    public static function ensureProductions(?User $user, Team $team): void
    {
        abort_unless($user !== null && self::viewProductions($user, $team), 403);
    }

    /**
     * OEE flags merged into the shared team-permissions payload.
     *
     * @return array{
     *     canRecordProduction: bool,
     *     canReopenProduction: bool,
     *     canDeleteProduction: bool,
     *     canManageCatalog: bool,
     *     canViewOee: bool,
     *     canViewParadas: bool,
     *     canViewProductions: bool,
     *     canViewCatalog: bool,
     *     canViewSkus: bool
     * }
     */
    public static function permissions(User $user, Team $team): array
    {
        $role = $user->teamRole($team);
        $managesPlant = ! $team->is_personal && ($role?->managesPlant() ?? false);
        $canViewParadas = self::viewParadas($user, $team);
        $canViewProductions = self::viewProductions($user, $team);
        $canViewCatalog = self::viewCatalog($user, $team);
        $canViewSkus = self::viewSkus($user, $team);

        return [
            'canRecordProduction' => $canViewProductions
                && ($role?->hasPermission(TeamPermission::RecordProduction) ?? false),
            'canReopenProduction' => $managesPlant
                && ($role?->hasPermission(TeamPermission::ReopenProduction) ?? false),
            'canDeleteProduction' => $managesPlant
                && ($role?->hasPermission(TeamPermission::DeleteProduction) ?? false),
            'canManageCatalog' => $managesPlant
                && ($role?->hasPermission(TeamPermission::ManageCatalog) ?? false),
            'canViewOee' => self::viewPanel($user, $team),
            'canViewParadas' => $canViewParadas,
            'canViewProductions' => $canViewProductions,
            'canViewCatalog' => $canViewCatalog,
            'canViewSkus' => $canViewSkus,
        ];
    }

    /**
     * Send a user without dashboard access to the first OEE screen they may open.
     */
    public static function fallback(User $user, Team $team): ?RedirectResponse
    {
        if ($user->hasSection($team, OeeSection::Oee)) {
            return to_route('oee.dashboard', $team);
        }

        if ($user->hasSection($team, OeeSection::Paradas)) {
            return to_route('oee.paradas', $team);
        }

        if ($user->hasSection($team, OeeSection::Productions)) {
            return to_route('oee.productions.index', $team);
        }

        if ($user->hasSection($team, OeeSection::Catalog)) {
            return to_route('oee.admin.stop-codes.index', $team);
        }

        if ($user->hasSection($team, OeeSection::Skus)) {
            return to_route('oee.admin.skus.index', $team);
        }

        return null;
    }
}
