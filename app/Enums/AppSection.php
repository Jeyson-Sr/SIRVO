<?php

namespace App\Enums;

use App\Data\SectionCatalog;

enum AppSection: string
{
    case Dashboard = 'dashboard';

    /**
     * Get the display label for the section.
     */
    public function label(): string
    {
        return 'Dashboard';
    }

    /**
     * Get every section value, including registered modules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return SectionCatalog::values();
    }

    /**
     * Get the sections as selectable options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return SectionCatalog::options();
    }

    /**
     * Platform sections an administrator may grant on their own.
     *
     * @return array<int, self>
     */
    public static function grantable(): array
    {
        return [self::Dashboard];
    }

    /**
     * @return array<int, string>
     */
    public static function grantableValues(): array
    {
        return SectionCatalog::grantableValues();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function grantableOptions(): array
    {
        return SectionCatalog::grantableOptions();
    }

    /**
     * A new operator or visor starts with the modules' defaults.
     *
     * @return array<int, string>
     */
    public static function operatorDefaults(): array
    {
        return SectionCatalog::operatorDefaults();
    }
}
