<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Access\OeeAccess;
use App\Modules\Oee\Http\Requests\ProductionFilterRequest;
use App\Modules\Oee\Queries\ExplainStops;
use App\Modules\Oee\Queries\LoadDashboardFilters;
use Inertia\Inertia;
use Inertia\Response;

class StopCommentController extends Controller
{
    /**
     * Display ranked stop codes with the comments, products and lines behind them.
     */
    public function __invoke(
        ProductionFilterRequest $request,
        ExplainStops $explainStops,
        LoadDashboardFilters $loadDashboardFilters,
        Team $current_team,
    ): Response {
        OeeAccess::ensureParadas($request->user(), $current_team);

        $filters = $request->filters();

        return Inertia::render('oee/paradas', [
            'codes' => Inertia::defer(fn () => $explainStops->handle(
                $current_team,
                $filters,
                $request->ranking(),
                $request->limit(),
            ), 'codes'),
            'filterOptions' => Inertia::once(fn () => $loadDashboardFilters->handle($current_team)),
            'appliedFilters' => $request->applied(),
        ]);
    }
}
