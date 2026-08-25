<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Models\OeeProduction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lists a team's recorded shifts for the production index.
 */
class ListProductions
{
    /**
     * Number of production runs listed per page.
     */
    public const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(Team $team, ?User $viewer): LengthAwarePaginator
    {
        return OeeProduction::query()
            ->forTeam($team)
            ->withCount([
                'hours',
                'hours as closed_hours_count' => fn ($query) => $query->where('closed', true),
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (OeeProduction $production): array => [
                'id' => $production->id,
                'fecha' => $production->fecha->toDateString(),
                'turno' => $production->turno->value,
                'turnoLabel' => $production->turno->label(),
                'linea' => $production->linea,
                'op' => $production->op,
                'marca' => $production->marca,
                'descripcion' => $production->descripcion,
                'hours' => $production->hours_count,
                'closedHours' => $production->closed_hours_count,
                'isClosed' => $production->isClosed(),
                'canEdit' => $viewer?->can('update', $production) ?? false,
                'canDelete' => $viewer?->can('delete', $production) ?? false,
            ]);
    }
}
