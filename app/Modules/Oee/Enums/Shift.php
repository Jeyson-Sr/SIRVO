<?php

namespace App\Modules\Oee\Enums;

enum Shift: string
{
    case Day = 'DIA';
    case Night = 'NOCHE';

    /**
     * Number of hour slots recorded per shift.
     */
    public const HOURS_PER_SHIFT = 12;

    /**
     * Get the display label for the shift.
     */
    public function label(): string
    {
        return match ($this) {
            self::Day => 'Día',
            self::Night => 'Noche',
        };
    }

    /**
     * Get the clock time the shift starts at, as "H:i".
     */
    public function startsAt(): string
    {
        return match ($this) {
            self::Day => '06:30',
            self::Night => '18:30',
        };
    }

    /**
     * Get the ordered hour ranges of the shift, e.g. "06:30 - 07:30".
     *
     * @return array<int, string>
     */
    public function hourRanges(): array
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->startsAt()));
        $start = $hour * 60 + $minute;

        return array_map(function (int $index) use ($start): string {
            $from = ($start + $index * 60) % 1440;
            $to = ($from + 60) % 1440;

            return sprintf('%s - %s', self::formatMinutes($from), self::formatMinutes($to));
        }, range(0, self::HOURS_PER_SHIFT - 1));
    }

    /**
     * Get the shifts as selectable options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $shift) => ['value' => $shift->value, 'label' => $shift->label()],
            self::cases(),
        );
    }

    /**
     * Format minutes past midnight as "H:i".
     */
    private static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
