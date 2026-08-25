<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Modules\Oee\Actions\CalculateOee;
use App\Modules\Oee\Actions\CalculateVolume;
use App\Modules\Oee\Data\HourSlice;
use App\Modules\Oee\Data\OeeMetrics;
use App\Modules\Oee\Data\ProductionFilters;
use Illuminate\Support\Collection;

/**
 * Builds every OEE breakdown a dashboard needs from a single pass of data.
 *
 * Reading and calculating are delegated: this class only decides how the hours
 * are grouped. Because every breakdown runs through the same calculator over the
 * same hours, the totals always reconcile with the per-line and per-day figures.
 */
class BuildOeeReport
{
    public function __construct(
        private LoadHourSlices $loadHourSlices,
        private CalculateOee $calculateOee,
        private CalculateVolume $calculateVolume,
    ) {
        //
    }

    /**
     * @return array{
     *     summary: array<string, mixed>,
     *     volumen: float,
     *     byLine: array<int, array<string, mixed>>,
     *     byDay: array<int, array<string, mixed>>,
     *     byWeek: array<int, array<string, mixed>>,
     * }
     */
    public function handle(Team $team, ProductionFilters $filters): array
    {
        $hourSlices = $this->loadHourSlices->handle($team, $filters);

        return [
            'summary' => $this->metricsFor($hourSlices)->toArray(),
            'volumen' => $this->outputOf($hourSlices),
            'byLine' => $this->groupedByLine($hourSlices),
            'byDay' => $this->groupedByDay($hourSlices),
            'byWeek' => $this->groupedByWeek($hourSlices),
        ];
    }

    /**
     * Break the figures down per production line, best performer first.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     * @return array<int, array<string, mixed>>
     */
    private function groupedByLine(Collection $hourSlices): array
    {
        return $hourSlices
            ->groupBy(fn (HourSlice $slice): string => $slice->linea)
            ->map(function (Collection $lineSlices, string $linea): array {
                $metrics = $this->metricsFor($lineSlices);

                return [
                    'linea' => $linea,
                    'oee' => $metrics->oee,
                    'em' => $metrics->em,
                    'volumen' => $this->outputOf($lineSlices),
                    'closedHours' => $metrics->closedHours,
                    'lossImpact' => $metrics->lossImpact,
                    'lossMinutes' => $metrics->lossMinutes,
                ];
            })
            ->sortByDesc('oee')
            ->values()
            ->all();
    }

    /**
     * Break the figures down per calendar day, oldest first.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     * @return array<int, array<string, mixed>>
     */
    private function groupedByDay(Collection $hourSlices): array
    {
        return $hourSlices
            ->groupBy(fn (HourSlice $slice): string => $slice->fecha->toDateString())
            ->map(function (Collection $daySlices, string $fecha): array {
                $metrics = $this->metricsFor($daySlices);

                return [
                    'fecha' => $fecha,
                    'label' => $daySlices->firstOrFail()->fecha->format('d/m'),
                    'oee' => $metrics->oee,
                    'em' => $metrics->em,
                    'volumen' => $this->outputOf($daySlices),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * Break the figures down per ISO week, oldest first.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     * @return array<int, array<string, mixed>>
     */
    private function groupedByWeek(Collection $hourSlices): array
    {
        return $hourSlices
            ->groupBy(fn (HourSlice $slice): string => $slice->isoWeek())
            ->map(function (Collection $weekSlices, string $isoWeek): array {
                $metrics = $this->metricsFor($weekSlices);
                $firstSlice = $weekSlices->firstOrFail();

                return [
                    'semana' => $isoWeek,
                    'label' => 'S'.$firstSlice->fecha->isoWeek,
                    'year' => $firstSlice->fecha->isoWeekYear,
                    'oee' => $metrics->oee,
                    'em' => $metrics->em,
                    'volumen' => $this->outputOf($weekSlices),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * Aggregate the downtime of a set of hours and derive their OEE figures.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     */
    private function metricsFor(Collection $hourSlices): OeeMetrics
    {
        $downtimeMinutesByType = [];

        foreach ($hourSlices as $slice) {
            foreach ($slice->minutesByType as $stopType => $minutes) {
                $downtimeMinutesByType[$stopType] = ($downtimeMinutesByType[$stopType] ?? 0.0) + $minutes;
            }
        }

        return $this->calculateOee->handle(
            $hourSlices->count(),
            $downtimeMinutesByType,
            (float) $hourSlices->sum(fn (HourSlice $slice) => $slice->durationMinutes),
        );
    }

    /**
     * Sum the consumer units recorded across a set of hours.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     */
    private function outputOf(Collection $hourSlices): float
    {
        return round($hourSlices->sum(
            fn (HourSlice $slice) => $this->calculateVolume->handle(
                $slice->producido,
                $slice->palletsPorHora,
                $slice->bph,
                $slice->litros,
            ),
        ), 2);
    }
}
