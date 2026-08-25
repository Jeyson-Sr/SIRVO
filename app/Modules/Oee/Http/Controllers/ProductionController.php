<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Actions\CloseProduction;
use App\Modules\Oee\Actions\SyncProduction;
use App\Modules\Oee\Http\Requests\SyncProductionRequest;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Queries\ListProductions;
use App\Modules\Oee\Queries\LoadRecordingForm;
use App\Modules\Oee\Queries\PresentProduction;
use App\Modules\Oee\Queries\PresentRecordingDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductionController extends Controller
{
    /**
     * Display the team's recorded production runs.
     */
    public function index(Request $request, ListProductions $listProductions, Team $current_team): Response
    {
        Gate::authorize('viewAny', [OeeProduction::class, $current_team]);

        return Inertia::render('oee/productions/index', [
            'productions' => $listProductions->handle($current_team, $request->user()),
        ]);
    }

    /**
     * Show the shift recording screen.
     */
    public function create(Request $request, LoadRecordingForm $loadRecordingForm, Team $current_team): Response
    {
        Gate::authorize('create', [OeeProduction::class, $current_team]);

        return Inertia::render('oee/productions/create', $loadRecordingForm->handle(
            $request->user()?->name ?? '',
        ));
    }

    /**
     * Display a single production run with its hours and stops.
     *
     * The team is declared so the route can scope the production to it; without
     * it in the signature the binding has no parent to resolve against.
     */
    public function show(
        Request $request,
        PresentProduction $presentProduction,
        Team $current_team,
        OeeProduction $production,
    ): Response {
        Gate::authorize('view', $production);

        return Inertia::render('oee/productions/show', [
            'production' => $presentProduction->handle($production),
            'canEdit' => $request->user()?->can('update', $production) ?? false,
            'canDelete' => $request->user()?->can('delete', $production) ?? false,
        ]);
    }

    /**
     * Show the form for editing a recorded shift.
     */
    public function edit(
        Request $request,
        LoadRecordingForm $loadRecordingForm,
        PresentRecordingDraft $presentRecordingDraft,
        Team $current_team,
        OeeProduction $production,
    ): Response {
        Gate::authorize('update', $production);

        return Inertia::render('oee/productions/create', [
            ...$loadRecordingForm->handle($request->user()?->name ?? ''),
            'productionId' => $production->id,
            'isClosed' => $production->isClosed(),
            'recording' => $presentRecordingDraft->handle($production),
        ]);
    }

    /**
     * Persist corrections to a recorded shift.
     */
    public function update(
        SyncProductionRequest $request,
        SyncProduction $syncProduction,
        Team $current_team,
        OeeProduction $production,
    ): RedirectResponse {
        Gate::authorize('update', $production);

        /** @var array{production: array<string, mixed>, hours: array<int, array<string, mixed>>} $payload */
        $payload = $request->validated();

        $payload['production']['fecha'] = $production->fecha->toDateString();
        $payload['production']['turno'] = $production->turno->value;
        $payload['production']['linea'] = $production->linea;
        $payload['production']['op'] = $production->op;

        $syncProduction->handle($current_team, $request->user(), $payload, $production);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Turno actualizado.']);

        return $this->redirectToProduction($current_team, $production);
    }

    /**
     * Remove a recorded shift and its hours.
     */
    public function destroy(Team $current_team, OeeProduction $production): RedirectResponse
    {
        Gate::authorize('delete', $production);

        $production->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Turno eliminado.']);

        return to_route('oee.productions.index', $current_team);
    }

    /**
     * Persist the submitted state of a production run.
     */
    public function store(SyncProductionRequest $request, SyncProduction $syncProduction, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [OeeProduction::class, $current_team]);

        /** @var array{production: array<string, mixed>, hours: array<int, array<string, mixed>>} $payload */
        $payload = $request->validated();

        $production = $syncProduction->handle($current_team, $request->user(), $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producción guardada.']);

        return $this->redirectToProduction($current_team, $production);
    }

    /**
     * Sign off a production run so its figures become historical record.
     */
    public function close(
        CloseProduction $closeProduction,
        Team $current_team,
        OeeProduction $production,
    ): RedirectResponse {
        Gate::authorize('close', $production);

        $closeProduction->handle($production);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Turno cerrado.']);

        return $this->redirectToProduction($current_team, $production);
    }

    /**
     * Reopen a signed-off production run so it can be corrected.
     */
    public function reopen(Team $current_team, OeeProduction $production): RedirectResponse
    {
        Gate::authorize('reopen', $production);

        $production->update(['closed_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Turno reabierto.']);

        return $this->redirectToProduction($current_team, $production);
    }

    /**
     * Send the user back to the production run they just acted on.
     */
    private function redirectToProduction(Team $team, OeeProduction $production): RedirectResponse
    {
        return to_route('oee.productions.show', [
            'current_team' => $team,
            'production' => $production->id,
        ]);
    }
}
