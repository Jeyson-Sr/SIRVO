<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Modules\Oee\Enums\StopRanking;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\OeeProduction;

/**
 * Values the dashboard filters can be set to.
 */
class LoadDashboardFilters
{
    /**
     * @return array{
     *     lineas: array<int, string>,
     *     marcas: array<int, string>,
     *     years: array<int, int>,
     *     componentes: array<int, array{value: string, label: string}>,
     *     sorts: array<int, array{value: string, label: string}>,
     * }
     */
    public function handle(Team $team): array
    {
        return [
            'lineas' => OeeProduction::query()
                ->forTeam($team)
                ->distinct()
                ->orderBy('linea')
                ->pluck('linea')
                ->all(),
            'marcas' => OeeProduction::query()
                ->forTeam($team)
                ->whereNotNull('marca')
                ->where('marca', '<>', '')
                ->distinct()
                ->orderBy('marca')
                ->pluck('marca')
                ->all(),
            'years' => $this->recordedYears($team),
            'componentes' => StopType::options(),
            'sorts' => StopRanking::options(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function recordedYears(Team $team): array
    {
        $earliestDate = OeeProduction::query()->forTeam($team)->min('fecha');
        $latestDate = OeeProduction::query()->forTeam($team)->max('fecha');

        if ($earliestDate === null || $latestDate === null) {
            return [];
        }

        return array_reverse(range(
            (int) substr((string) $earliestDate, 0, 4),
            (int) substr((string) $latestDate, 0, 4),
        ));
    }
}
