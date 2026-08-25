<?php

namespace App\Modules\Oee\Enums;

/**
 * The measure a stop-code ranking is ordered by.
 */
enum StopRanking: string
{
    case Minutes = 'minutes';
    case Frequency = 'frequency';

    /**
     * Get the display label for the measure.
     */
    public function label(): string
    {
        return match ($this) {
            self::Minutes => 'Minutos perdidos',
            self::Frequency => 'Frecuencia',
        };
    }

    /**
     * Get the aggregate column the ranking orders by.
     */
    public function column(): string
    {
        return match ($this) {
            self::Minutes => 'total_minutos',
            self::Frequency => 'total_frecuencia',
        };
    }

    /**
     * Get the ranking measures as selectable options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $sort) => ['value' => $sort->value, 'label' => $sort->label()],
            self::cases(),
        );
    }
}
