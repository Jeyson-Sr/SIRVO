<?php

namespace App\Modules\Oee\Skus;

use App\Http\Controllers\Controller;
use App\Modules\Oee\OeeAccess;
use App\Modules\Oee\Shared\CatalogSearchRequest;
use App\Modules\Teams\Team;
use Illuminate\Http\JsonResponse;

class SkuController extends Controller
{
    /**
     * Search the finished-goods catalog.
     *
     * Answers the type-ahead on the recording screen, so it returns JSON
     * rather than an Inertia page.
     */
    public function index(CatalogSearchRequest $request, SearchSkus $searchSkus, Team $current_team): JsonResponse
    {
        OeeAccess::ensureProductions($request->user(), $current_team);

        return response()->json([
            'data' => $searchSkus->handle(
                $request->validated('search'),
                $request->validated('linea'),
            ),
        ]);
    }
}
