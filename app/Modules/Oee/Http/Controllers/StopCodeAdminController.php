<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Http\Requests\ListStopCodesRequest;
use App\Modules\Oee\Http\Requests\StoreStopCodeRequest;
use App\Modules\Oee\Http\Requests\UpdateStopCodeRequest;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Queries\ListStopCodes;
use App\Modules\Oee\Queries\PresentStopCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StopCodeAdminController extends Controller
{
    /**
     * Display the stop-code catalog for administrators.
     */
    public function index(
        ListStopCodesRequest $request,
        ListStopCodes $listStopCodes,
        Team $current_team,
    ): Response {
        Gate::authorize('viewAny', [CodStop::class, $current_team]);

        return Inertia::render('oee/admin/stop-codes/index', [
            'codes' => $listStopCodes->handle(
                $request->validated('search'),
                $request->stopType(),
                $request->validated('es_tetra_pak'),
                $request->validated('activo'),
            ),
            'types' => StopType::adminOptions(),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Show the form for creating a catalog entry.
     */
    public function create(Team $current_team): Response
    {
        Gate::authorize('create', [CodStop::class, $current_team]);

        return Inertia::render('oee/admin/stop-codes/create', [
            'types' => StopType::adminOptions(),
        ]);
    }

    /**
     * Store a newly created catalog entry.
     */
    public function store(StoreStopCodeRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [CodStop::class, $current_team]);

        CodStop::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Código de parada creado.']);

        return to_route('oee.admin.stop-codes.index', $current_team);
    }

    /**
     * Show the form for editing a catalog entry.
     */
    public function edit(PresentStopCode $presentStopCode, Team $current_team, CodStop $codStop): Response
    {
        Gate::authorize('update', [$codStop, $current_team]);

        return Inertia::render('oee/admin/stop-codes/edit', [
            'code' => $presentStopCode->form($codStop),
            'types' => StopType::adminOptions(),
            'inUse' => $codStop->isInUse(),
        ]);
    }

    /**
     * Update the specified catalog entry.
     */
    public function update(UpdateStopCodeRequest $request, Team $current_team, CodStop $codStop): RedirectResponse
    {
        Gate::authorize('update', [$codStop, $current_team]);

        $codStop->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Código de parada actualizado.']);

        return to_route('oee.admin.stop-codes.index', $current_team);
    }

    /**
     * Remove the specified catalog entry.
     */
    public function destroy(Team $current_team, CodStop $codStop): RedirectResponse
    {
        Gate::authorize('delete', [$codStop, $current_team]);

        if ($codStop->isInUse()) {
            return back()->withErrors([
                'codigo' => 'Este código ya se usó en un turno. Desactívalo en lugar de eliminarlo.',
            ]);
        }

        $codStop->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Código de parada eliminado.']);

        return to_route('oee.admin.stop-codes.index', $current_team);
    }
}
