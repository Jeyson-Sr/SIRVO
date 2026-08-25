<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Access\OeeAccess;
use App\Modules\Oee\Http\Requests\ProductionFilterRequest;
use App\Modules\Oee\Queries\BuildOeeReport;
use App\Modules\Oee\Queries\LoadDashboardFilters;
use App\Modules\Oee\Queries\RankStops;
use Inertia\Inertia;
use Inertia\Response;

class OeeDashboardController extends Controller
{
    /**
     * Display the OEE dashboard for the current team.
     *
     * The figures are deferred so the shell paints immediately; the aggregation
     * arrives in a follow-up request and the filter options are fetched once.
     */
    public function __invoke(
        ProductionFilterRequest $request,
        BuildOeeReport $buildOeeReport,
        RankStops $rankStops,
        LoadDashboardFilters $loadDashboardFilters,
        Team $current_team,
    ): Response {
        OeeAccess::ensurePanel($request->user(), $current_team);

        $filters = $request->filters();

        return Inertia::render('oee/dashboard', [
            'report' => Inertia::defer(fn () => $buildOeeReport->handle($current_team, $filters)),
            'ranking' => Inertia::defer(fn () => $rankStops->handle(
                $current_team,
                $filters,
                $request->ranking(),
                $request->limit(),
            ), 'ranking'),
            'filterOptions' => Inertia::once(fn () => $loadDashboardFilters->handle($current_team)),
            'appliedFilters' => $request->applied(),
        ]);
    }
}
