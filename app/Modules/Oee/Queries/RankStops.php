<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Modules\Oee\Concerns\FiltersProductionQueries;
use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopRanking;
use App\Modules\Oee\Enums\StopType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ranks the stop codes responsible for the most downtime.
 *
 * Shares are measured against the total downtime of the period rather than
 * against the truncated top-N, so the cumulative column is a usable Pareto curve
 * and "the top 5 codes explain 80% of losses" actually means that.
 */
class RankStops
{
    use FiltersProductionQueries;

    public const DEFAULT_LIMIT = 15;

    public const MAX_LIMIT = 50;

    /**
     * @return array<int, array{
     *     codigo: string,
     *     descripcion: string|null,
     *     tipo: string,
     *     tipoLabel: string,
     *     totalMinutos: float,
     *     totalFrecuencia: int,
     *     porcentaje: float,
     *     porcentajeAcumulado: float,
     * }>
     */
    public function handle(
        Team $team,
        ProductionFilters $filters,
        StopRanking $sortBy = StopRanking::Minutes,
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        $requestedLimit = max(1, min($limit, self::MAX_LIMIT));

        $rankedRows = $this->downtimeQuery($team, $filters)
            ->groupBy('s.codigo', 's.tipo')
            ->selectRaw('s.codigo as codigo, s.tipo as tipo, MAX(s.descripcion) as descripcion, SUM(s.tiempo_minutos) as total_minutos, SUM(s.frecuencia) as total_frecuencia')
            ->orderByDesc($sortBy->column())
            ->orderBy('s.codigo')
            ->limit($requestedLimit)
            ->get();

        $periodDowntimeMinutes = (float) $this->downtimeQuery($team, $filters)->sum('s.tiempo_minutos');
        $cumulativePercentage = 0.0;

        return $rankedRows->map(function (object $rankedRow) use ($periodDowntimeMinutes, &$cumulativePercentage): array {
            $codeDowntimeMinutes = (float) $rankedRow->total_minutos;

            $sharePercentage = $periodDowntimeMinutes > 0.0
                ? ($codeDowntimeMinutes / $periodDowntimeMinutes) * 100
                : 0.0;

            $cumulativePercentage += $sharePercentage;

            // The column is written from the catalog, so it always holds a known code.
            $stopType = StopType::from((string) $rankedRow->tipo);

            return [
                'codigo' => (string) $rankedRow->codigo,
                'descripcion' => $rankedRow->descripcion === null ? null : (string) $rankedRow->descripcion,
                'tipo' => $stopType->value,
                'tipoLabel' => $stopType->label(),
                'totalMinutos' => round($codeDowntimeMinutes, 2),
                'totalFrecuencia' => (int) $rankedRow->total_frecuencia,
                'porcentaje' => round($sharePercentage, 2),
                'porcentajeAcumulado' => round(min($cumulativePercentage, 100.0), 2),
            ];
        })->all();
    }

    /**
     * Start a downtime query narrowed to the requested team, period and family.
     */
    private function downtimeQuery(Team $team, ProductionFilters $filters): Builder
    {
        return $this->applyProductionFilters(
            DB::table('oee_stop_details as s')
                ->join('oee_hour_details as h', 'h.id', '=', 's.oee_hour_detail_id')
                ->join('oee_productions as p', 'p.id', '=', 'h.oee_production_id'),
            $team,
            $filters,
        )->when(
            $filters->componente,
            fn (Builder $query, StopType $stopType) => $query->where('s.tipo', $stopType->value),
        );
    }
}
