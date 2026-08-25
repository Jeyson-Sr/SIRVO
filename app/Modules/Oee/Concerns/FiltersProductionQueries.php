<?php

namespace App\Modules\Oee\Concerns;

use App\Models\Team;
use App\Modules\Oee\Data\ProductionFilters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;

/**
 * Shared narrowing for reporting queries over production hours.
 *
 * Every consumer applies the same predicates, so two reports built from the same
 * filters always describe the same rows.
 */
trait FiltersProductionQueries
{
    /**
     * Narrow a query that has `oee_hour_details as h` joined to
     * `oee_productions as p` down to the requested team and filters.
     */
    protected function applyProductionFilters(Builder $query, Team $team, ProductionFilters $filters): Builder
    {
        return $query
            ->where('p.team_id', $team->id)
            ->when($filters->closedOnly, fn (Builder $query) => $query->where('h.closed', true))
            ->when($filters->from, fn (Builder $query, CarbonImmutable $from) => $query->where('p.fecha', '>=', $from->toDateString()))
            ->when($filters->to, fn (Builder $query, CarbonImmutable $to) => $query->where('p.fecha', '<=', $to->toDateString()))
            ->when($filters->linea, fn (Builder $query, string $linea) => $query->where('p.linea', $linea))
            ->when($filters->marca, fn (Builder $query, string $marca) => $query->where('p.marca', $marca));
    }
}
