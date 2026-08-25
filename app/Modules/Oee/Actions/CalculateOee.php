<?php

namespace App\Modules\Oee\Actions;

use App\Modules\Oee\Data\OeeMetrics;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\OeeHourDetail;

/**
 * Turns closed production hours and their downtime into OEE figures.
 *
 * This is the only place the OEE formula lives. Every screen, chart and export
 * resolves its numbers through here so they cannot disagree with each other.
 *
 * It depends on no database and no request, which is what makes the formula
 * cheap to test exhaustively.
 */
class CalculateOee
{
    /**
     * Derive the OEE figures for a number of closed hours and their downtime.
     *
     * @param  array<string, float>  $downtimeMinutesByType  Minutes keyed by StopType value.
     */
    public function handle(int $closedHours, array $downtimeMinutesByType, ?float $scheduledMinutes = null): OeeMetrics
    {
        $scheduledMinutes = $scheduledMinutes ?? max(0, $closedHours) * OeeHourDetail::MINUTES_PER_HOUR;

        if ($scheduledMinutes <= 0) {
            return OeeMetrics::empty($closedHours);
        }

        $downtimeMinutes = $this->completeAndClamp($downtimeMinutesByType);

        // Unscheduled time is not a loss: the line was never expected to run, so
        // it shrinks the window being measured instead of counting against it.
        $unscheduledMinutes = min(
            $downtimeMinutes[StopType::Unscheduled->value],
            (float) $scheduledMinutes,
        );

        $effectiveMinutes = $scheduledMinutes - $unscheduledMinutes;

        if ($effectiveMinutes <= 0.0) {
            return OeeMetrics::withoutEffectiveTime(
                closedHours: $closedHours,
                scheduledMinutes: (float) $scheduledMinutes,
                unscheduledMinutes: round($unscheduledMinutes, 2),
                lossMinutes: array_map(fn (float $minutes) => round($minutes, 2), $downtimeMinutes),
            );
        }

        $lossMinutes = $this->totalLossMinutes($downtimeMinutes);
        $productiveMinutes = max(0.0, $effectiveMinutes - $lossMinutes);
        $equipmentMinutes = $downtimeMinutes[StopType::Equipment->value];

        return new OeeMetrics(
            closedHours: $closedHours,
            scheduledMinutes: (float) $scheduledMinutes,
            unscheduledMinutes: round($unscheduledMinutes, 2),
            effectiveMinutes: round($effectiveMinutes, 2),
            productiveMinutes: round($productiveMinutes, 2),
            oee: $this->asPercentage($productiveMinutes / $effectiveMinutes),
            // EM isolates equipment reliability: everything except machine failure
            // is treated as available time.
            em: $this->asPercentage(($effectiveMinutes - $equipmentMinutes) / $effectiveMinutes),
            lossMinutes: array_map(fn (float $minutes) => round($minutes, 2), $downtimeMinutes),
            lossImpact: array_map(
                fn (float $minutes) => $this->asPercentage($minutes / $effectiveMinutes),
                $downtimeMinutes,
            ),
        );
    }

    /**
     * Sum the downtime of every family that counts against productive time.
     *
     * @param  array<string, float>  $downtimeMinutes
     */
    private function totalLossMinutes(array $downtimeMinutes): float
    {
        return array_sum(array_map(
            fn (StopType $type) => $downtimeMinutes[$type->value],
            StopType::losses(),
        ));
    }

    /**
     * Give every stop type an entry and discard negative figures.
     *
     * Callers pass whatever the database returned, so the rest of the method can
     * rely on all seven keys being present.
     *
     * @param  array<string, float>  $downtimeMinutesByType
     * @return array<string, float>
     */
    private function completeAndClamp(array $downtimeMinutesByType): array
    {
        $completed = [];

        foreach (StopType::cases() as $type) {
            $completed[$type->value] = max(0.0, (float) ($downtimeMinutesByType[$type->value] ?? 0.0));
        }

        return $completed;
    }

    /**
     * Express a ratio as a percentage bounded to 0-100.
     */
    private function asPercentage(float $ratio): float
    {
        return round(max(0.0, min(1.0, $ratio)) * 100, 2);
    }
}
