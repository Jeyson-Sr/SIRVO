<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Access\OeeAccess;
use App\Modules\Oee\Http\Requests\CatalogSearchRequest;
use App\Modules\Oee\Queries\SearchStopCodes;
use Illuminate\Http\JsonResponse;

class StopCodeController extends Controller
{
    /**
     * Search the stop code catalog.
     *
     * Answers the type-ahead in the stop recording dialog, so it returns JSON
     * rather than an Inertia page.
     */
    public function index(CatalogSearchRequest $request, SearchStopCodes $searchStopCodes, Team $current_team): JsonResponse
    {
        OeeAccess::ensureProductions($request->user(), $current_team);

        return response()->json([
            'data' => $searchStopCodes->handle($request->validated('search')),
        ]);
    }
}
