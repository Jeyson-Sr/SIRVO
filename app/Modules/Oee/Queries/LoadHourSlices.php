<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Modules\Oee\Concerns\FiltersProductionQueries;
use App\Modules\Oee\Data\HourSlice;
use App\Modules\Oee\Data\ProductionFilters;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reads the production hours a report covers, with their downtime attached.
 *
 * Two queries are issued no matter how much data matches: one for the hours and
 * one for their downtime totals. Grouping is left to the caller, which is what
 * keeps a dashboard at a fixed query count instead of one query per line and day.
 */
class LoadHourSlices
{
    use FiltersProductionQueries;

    /**
     * @return Collection<int, HourSlice>
     */
    public function handle(Team $team, ProductionFilters $filters): Collection
    {
        $hourRows = $this->applyProductionFilters(
            DB::table('oee_hour_details as h')
                ->join('oee_productions as p', 'p.id', '=', 'h.oee_production_id'),
            $team,
            $filters,
        )->select(
            'h.id',
            'h.producido',
            'h.duration_minutes',
            'h.formato as hour_formato',
            'h.pallets_por_hora as hour_pallets_por_hora',
            'h.bph as hour_bph',
            'p.fecha',
            'p.linea',
            'p.marca',
            'p.formato',
            'p.pallets_por_hora',
            'p.bph',
        )->get();

        if ($hourRows->isEmpty()) {
            return new Collection;
        }

        $downtimeByHour = $this->downtimeMinutesByHour($team, $filters);

        return $hourRows->map(fn (object $hourRow) => new HourSlice(
            hourId: (int) $hourRow->id,
            fecha: CarbonImmutable::parse((string) $hourRow->fecha),
            linea: (string) $hourRow->linea,
            marca: $hourRow->marca === null ? null : (string) $hourRow->marca,
            producido: (float) ($hourRow->producido ?? 0),
            palletsPorHora: (float) (($hourRow->hour_pallets_por_hora ?? 0) > 0 ? $hourRow->hour_pallets_por_hora : $hourRow->pallets_por_hora),
            bph: (float) (($hourRow->hour_bph ?? 0) > 0 ? $hourRow->hour_bph : $hourRow->bph),
            litros: (float) (($hourRow->hour_formato ?? null) !== null && $hourRow->hour_formato !== ''
                ? $hourRow->hour_formato
                : ($hourRow->formato ?? 0)),
            durationMinutes: (float) ($hourRow->duration_minutes ?? 60),
            minutesByType: $downtimeByHour[(int) $hourRow->id] ?? [],
        ))->values();
    }

    /**
     * Sum the downtime of every matching hour, grouped by hour and stop type.
     *
     * The stop rows carry their own `tipo` snapshot, so the catalog is not joined
     * here and re-categorising a code later cannot rewrite past figures.
     *
     * @return array<int, array<string, float>>
     */
    private function downtimeMinutesByHour(Team $team, ProductionFilters $filters): array
    {
        $downtimeRows = $this->applyProductionFilters(
            DB::table('oee_stop_details as s')
                ->join('oee_hour_details as h', 'h.id', '=', 's.oee_hour_detail_id')
                ->join('oee_productions as p', 'p.id', '=', 'h.oee_production_id'),
            $team,
            $filters,
        )
            ->groupBy('s.oee_hour_detail_id', 's.tipo')
            ->selectRaw('s.oee_hour_detail_id as hour_id, s.tipo as tipo, SUM(s.tiempo_minutos) as minutos')
            ->get();

        $downtimeByHour = [];

        foreach ($downtimeRows as $downtimeRow) {
            $hourId = (int) $downtimeRow->hour_id;
            $stopType = (string) $downtimeRow->tipo;

            $downtimeByHour[$hourId][$stopType] = (float) $downtimeRow->minutos;
        }

        return $downtimeByHour;
    }
}
