<?php

namespace App\Modules\Oee\Reporting;

use App\Modules\Oee\Production\HourSlice;
use App\Modules\Oee\Production\LoadHourSlices;
use App\Modules\Oee\Production\ProductionFilters;
use App\Modules\Teams\Team;
use Carbon\CarbonImmutable;
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
     *     parihuelas: float,
     *     byLine: array<int, array<string, mixed>>,
     *     byDay: array<int, array<string, mixed>>,
     *     byWeek: array<int, array<string, mixed>>,
     *     volumenPorDia: array<int, array{fecha: string, label: string, volumen: float}>,
     * }
     */
    public function handle(Team $team, ProductionFilters $filters): array
    {
        $hourSlices = $this->loadHourSlices->handle($team, $filters);

        return [
            'summary' => $this->metricsFor($hourSlices)->toArray(),
            'volumen' => $this->outputOf($hourSlices),
            'parihuelas' => $this->palletsOf($hourSlices),
            'byLine' => $this->groupedByLine($hourSlices),
            'byDay' => $this->groupedByDay($hourSlices),
            'byWeek' => $this->groupedByWeek($hourSlices),
            'volumenPorDia' => $this->volumenPorDia($hourSlices, $filters),
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
     * List consumer units for every day of the filtered range.
     *
     * With no date filter the list stays on the current month, so the panel can
     * keep its all-time totals while this breakdown still reads as a calendar.
     * Days without closed hours stay in the list at zero.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     * @return array<int, array{fecha: string, label: string, volumen: float}>
     */
    private function volumenPorDia(Collection $hourSlices, ProductionFilters $filters): array
    {
        [$from, $to] = $this->volumeWindow($hourSlices, $filters);

        $slicesByDay = $hourSlices
            ->filter(fn (HourSlice $slice): bool => $slice->fecha->betweenIncluded($from, $to))
            ->groupBy(fn (HourSlice $slice): string => $slice->fecha->toDateString());

        $days = [];

        for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            /** @var Collection<int, HourSlice> $daySlices */
            $daySlices = $slicesByDay->get($key) ?? new Collection;

            $days[] = [
                'fecha' => $key,
                'label' => $day->format('d/m'),
                'volumen' => $this->outputOf($daySlices),
            ];
        }

        return $days;
    }

    /**
     * Resolve the calendar the daily volume list should cover.
     *
     * An open start walks back to the first recorded day inside the filter.
     * An open end stops at today, which is the last day that can have hours.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function volumeWindow(Collection $hourSlices, ProductionFilters $filters): array
    {
        $today = CarbonImmutable::today();

        if (! $filters->hasDateRange()) {
            return [$today->startOfMonth(), $today->endOfMonth()->startOfDay()];
        }

        $to = ($filters->to ?? $today)->startOfDay();
        $from = $filters->from?->startOfDay() ?? $this->earliestDay($hourSlices, $to);

        return [$from, $to];
    }

    /**
     * Get the first recorded day that still falls on or before the window end.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     */
    private function earliestDay(Collection $hourSlices, CarbonImmutable $to): CarbonImmutable
    {
        $earliest = $hourSlices
            ->map(fn (HourSlice $slice): CarbonImmutable => $slice->fecha->startOfDay())
            ->filter(fn (CarbonImmutable $fecha): bool => $fecha->lessThanOrEqualTo($to))
            ->sort()
            ->first();

        return $earliest ?? $to->startOfMonth();
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

    /**
     * Sum the pallets recorded across a set of hours.
     *
     * @param  Collection<int, HourSlice>  $hourSlices
     */
    private function palletsOf(Collection $hourSlices): float
    {
        return round($hourSlices->sum(
            fn (HourSlice $slice) => $slice->producido,
        ), 2);
    }
}
