<?php

namespace App\Modules\Oee\Stops;

use App\Http\Controllers\Controller;
use App\Modules\Oee\OeeAccess;
use App\Modules\Oee\Shared\CatalogSearchRequest;
use App\Modules\Teams\Team;
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

        $tipo = StopType::tryFrom((string) ($request->validated('tipo') ?? ''));

        return response()->json([
            'data' => $searchStopCodes->handle($request->validated('search'), $tipo),
        ]);
    }
}
