<?php

namespace App\Modules\Oee\Stops;

use App\Http\Controllers\Controller;
use App\Modules\Oee\OeeAccess;
use App\Modules\Oee\Production\ProductionFilterRequest;
use App\Modules\Oee\Reporting\LoadDashboardFilters;
use App\Modules\Teams\Team;
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
