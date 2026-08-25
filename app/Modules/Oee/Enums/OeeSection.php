<?php

namespace App\Modules\Oee\Enums;

enum OeeSection: string
{
    case Oee = 'oee';
    case Productions = 'productions';
    case Catalog = 'catalog';
    case Skus = 'skus';

    /**
     * Get the display label for the section.
     */
    public function label(): string
    {
        return match ($this) {
            self::Oee => 'Panel OEE',
            self::Productions => 'Turnos',
            self::Catalog => 'Códigos de parada',
            self::Skus => 'Productos',
        };
    }

    /**
     * Get the sections as selectable options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $section) => ['value' => $section->value, 'label' => $section->label()],
            self::cases(),
        );
    }

    /**
     * A new operator or visor starts with Panel OEE only.
     *
     * @return array<int, string>
     */
    public static function operatorDefaults(): array
    {
        return [self::Oee->value];
    }
}
