<?php

namespace App\Data;

use App\Enums\AppSection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class SectionCatalog
{
    /**
     * Every stored section value: dashboard plus each registered module.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            AppSection::Dashboard->value,
            ...self::moduleValues(),
        ];
    }

    /**
     * Sections an administrator may grant, including dashboard.
     *
     * @return array<int, string>
     */
    public static function grantableValues(): array
    {
        return self::values();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => AppSection::Dashboard->value, 'label' => AppSection::Dashboard->label()],
            ...self::moduleOptions(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function grantableOptions(): array
    {
        return self::options();
    }

    /**
     * Defaults for a new operator: module defaults only, not dashboard.
     *
     * @return array<int, string>
     */
    public static function operatorDefaults(): array
    {
        $defaults = [];

        foreach (self::sectionEnums() as $enum) {
            $defaults = [...$defaults, ...$enum::operatorDefaults()];
        }

        return $defaults;
    }

    /**
     * Module permission flags merged into the shared team-permissions payload.
     *
     * @return array<string, bool>
     */
    public static function permissions(User $user, Team $team): array
    {
        $flags = [];

        foreach (self::accessClasses() as $access) {
            $flags = [...$flags, ...$access::permissions($user, $team)];
        }

        return $flags;
    }

    /**
     * Redirect a user without dashboard access to the first module they may open.
     */
    public static function fallback(User $user, Team $team): ?RedirectResponse
    {
        foreach (self::accessClasses() as $access) {
            $redirect = $access::fallback($user, $team);

            if ($redirect !== null) {
                return $redirect;
            }
        }

        return null;
    }

    /**
     * @return array<int, class-string>
     */
    private static function sectionEnums(): array
    {
        return config('modules.sections', []);
    }

    /**
     * @return array<int, class-string>
     */
    private static function accessClasses(): array
    {
        return config('modules.access', []);
    }

    /**
     * @return array<int, string>
     */
    private static function moduleValues(): array
    {
        $values = [];

        foreach (self::sectionEnums() as $enum) {
            $values = [...$values, ...array_column($enum::cases(), 'value')];
        }

        return $values;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function moduleOptions(): array
    {
        $options = [];

        foreach (self::sectionEnums() as $enum) {
            $options = [...$options, ...$enum::options()];
        }

        return $options;
    }
}
