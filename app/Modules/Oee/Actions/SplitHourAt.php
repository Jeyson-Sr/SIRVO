<?php

namespace App\Modules\Oee\Actions;

use InvalidArgumentException;

/**
 * Splits a clock-hour slot at the minute a product change happens.
 *
 * Each side keeps its own duration so the pallet target (PH) is charged only
 * for the minutes that SKU actually ran.
 */
class SplitHourAt
{
    /**
     * @return array{
     *     before: array{hour_range: string, duration_minutes: float, estimado: float},
     *     after: array{hour_range: string, duration_minutes: float, estimado: float},
     * }
     */
    public function handle(string $hourRange, string $cutTime, float $palletsBefore, float $palletsAfter): array
    {
        [$from, $to] = $this->endsOf($hourRange);
        $start = $this->minutesFromMidnight($from);
        $end = $this->minutesFromMidnight($to);
        $cut = $this->minutesFromMidnight($cutTime);

        $slotMinutes = $this->elapsed($start, $end);
        $beforeMinutes = $this->elapsed($start, $cut);

        if ($beforeMinutes <= 0 || $beforeMinutes >= $slotMinutes) {
            throw new InvalidArgumentException('La hora de cambio debe quedar dentro del rango de la hora.');
        }

        $afterMinutes = $slotMinutes - $beforeMinutes;

        return [
            'before' => [
                'hour_range' => $this->range($from, $cutTime),
                'duration_minutes' => (float) $beforeMinutes,
                'estimado' => $this->target($palletsBefore, $beforeMinutes),
            ],
            'after' => [
                'hour_range' => $this->range($cutTime, $to),
                'duration_minutes' => (float) $afterMinutes,
                'estimado' => $this->target($palletsAfter, $afterMinutes),
            ],
        ];
    }

    /**
     * Pallet target for a slice: hourly PH scaled to the minutes it ran.
     */
    public function target(float $palletsPerHour, float $durationMinutes): float
    {
        if ($palletsPerHour <= 0.0 || $durationMinutes <= 0.0) {
            return 0.0;
        }

        return round($palletsPerHour * $durationMinutes / 60, 2);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function endsOf(string $hourRange): array
    {
        $parts = array_map('trim', explode('-', $hourRange, 2));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('El rango de hora no es válido.');
        }

        return [$parts[0], $parts[1]];
    }

    private function minutesFromMidnight(string $clock): int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $clock, $matches)) {
            throw new InvalidArgumentException('La hora no es válida.');
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            throw new InvalidArgumentException('La hora no es válida.');
        }

        return $hour * 60 + $minute;
    }

    private function elapsed(int $from, int $to): int
    {
        $minutes = $to - $from;

        if ($minutes <= 0) {
            $minutes += 1440;
        }

        return $minutes;
    }

    private function range(string $from, string $to): string
    {
        return sprintf('%s - %s', $from, $to);
    }
}
