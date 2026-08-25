<?php

namespace App\Modules\Oee\Data;

use App\Modules\Oee\Enums\StopType;

/**
 * The OEE figures for an arbitrary set of production hours.
 */
readonly class OeeMetrics
{
    /**
     * @param  array<string, float>  $lossMinutes  Downtime minutes keyed by StopType value.
     * @param  array<string, float>  $lossImpact  Share of effective time lost, keyed by StopType value.
     */
    public function __construct(
        public int $closedHours,
        public float $scheduledMinutes,
        public float $unscheduledMinutes,
        public float $effectiveMinutes,
        public float $productiveMinutes,
        public float $oee,
        public float $em,
        public array $lossMinutes,
        public array $lossImpact,
    ) {
        //
    }

    /**
     * Get the metrics for a period with no closed hours behind it.
     */
    public static function empty(int $closedHours = 0): self
    {
        return new self(
            closedHours: $closedHours,
            scheduledMinutes: 0.0,
            unscheduledMinutes: 0.0,
            effectiveMinutes: 0.0,
            productiveMinutes: 0.0,
            oee: 0.0,
            em: 0.0,
            lossMinutes: self::zeroedByType(),
            lossImpact: self::zeroedByType(),
        );
    }

    /**
     * Get the metrics for hours that were scheduled but never expected to run.
     *
     * The percentages are meaningless without effective time, but the recorded
     * downtime still has to be reported or the breakdown would hide it.
     *
     * @param  array<string, float>  $lossMinutes
     */
    public static function withoutEffectiveTime(
        int $closedHours,
        float $scheduledMinutes,
        float $unscheduledMinutes,
        array $lossMinutes,
    ): self {
        return new self(
            closedHours: $closedHours,
            scheduledMinutes: $scheduledMinutes,
            unscheduledMinutes: $unscheduledMinutes,
            effectiveMinutes: 0.0,
            productiveMinutes: 0.0,
            oee: 0.0,
            em: 0.0,
            lossMinutes: $lossMinutes,
            lossImpact: self::zeroedByType(),
        );
    }

    /**
     * Get a zero entry for every stop type.
     *
     * @return array<string, float>
     */
    private static function zeroedByType(): array
    {
        return array_fill_keys(
            array_map(fn (StopType $type) => $type->value, StopType::cases()),
            0.0,
        );
    }

    /**
     * Get the downtime minutes attributed to the given stop type.
     */
    public function minutesFor(StopType $type): float
    {
        return $this->lossMinutes[$type->value] ?? 0.0;
    }

    /**
     * Get the share of effective time lost to the given stop type.
     */
    public function impactFor(StopType $type): float
    {
        return $this->lossImpact[$type->value] ?? 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'oee' => $this->oee,
            'em' => $this->em,
            'closedHours' => $this->closedHours,
            'scheduledMinutes' => $this->scheduledMinutes,
            'unscheduledMinutes' => $this->unscheduledMinutes,
            'effectiveMinutes' => $this->effectiveMinutes,
            'productiveMinutes' => $this->productiveMinutes,
            'lossMinutes' => $this->lossMinutes,
            'lossImpact' => $this->lossImpact,
        ];
    }
}
