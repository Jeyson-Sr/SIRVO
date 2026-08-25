<?php

namespace App\Modules\Oee\Data;

use Carbon\CarbonImmutable;

/**
 * A closed production hour reduced to the figures reporting needs.
 *
 * Slices are loaded in bulk and then grouped in memory, so a dashboard costs a
 * fixed number of queries regardless of how many lines or days it spans.
 */
readonly class HourSlice
{
    /**
     * @param  array<string, float>  $minutesByType  Downtime minutes keyed by StopType value.
     */
    public function __construct(
        public int $hourId,
        public CarbonImmutable $fecha,
        public string $linea,
        public ?string $marca,
        public float $producido,
        public float $palletsPorHora,
        public float $bph,
        public float $litros,
        public float $durationMinutes,
        public array $minutesByType,
    ) {
        //
    }

    /**
     * Get the ISO year and week the hour belongs to, e.g. "2026-W33".
     */
    public function isoWeek(): string
    {
        return sprintf('%d-W%02d', $this->fecha->isoWeekYear, $this->fecha->isoWeek);
    }
}
