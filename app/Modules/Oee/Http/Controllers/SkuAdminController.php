<?php

namespace App\Modules\Oee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Modules\Oee\Http\Requests\ListSkusRequest;
use App\Modules\Oee\Http\Requests\StoreSkuRequest;
use App\Modules\Oee\Http\Requests\UpdateSkuRequest;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Queries\ListSkus;
use App\Modules\Oee\Queries\PresentSku;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SkuAdminController extends Controller
{
    /**
     * Display the finished-goods catalog for administrators.
     */
    public function index(
        ListSkusRequest $request,
        ListSkus $listSkus,
        Team $current_team,
    ): Response {
        Gate::authorize('viewAny', [OeeSku::class, $current_team]);

        return Inertia::render('oee/admin/skus/index', [
            'skus' => $listSkus->handle(
                $request->validated('search'),
                $request->validated('linea'),
                $request->validated('activo'),
            ),
            'lines' => $this->lines(),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Show the form for creating a catalog entry.
     */
    public function create(Team $current_team): Response
    {
        Gate::authorize('create', [OeeSku::class, $current_team]);

        return Inertia::render('oee/admin/skus/create', [
            'lines' => $this->lines(),
        ]);
    }

    /**
     * Store a newly created catalog entry.
     */
    public function store(StoreSkuRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [OeeSku::class, $current_team]);

        OeeSku::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto creado.']);

        return to_route('oee.admin.skus.index', $current_team);
    }

    /**
     * Show the form for editing a catalog entry.
     */
    public function edit(PresentSku $presentSku, Team $current_team, OeeSku $oeeSku): Response
    {
        Gate::authorize('update', [$oeeSku, $current_team]);

        return Inertia::render('oee/admin/skus/edit', [
            'sku' => $presentSku->form($oeeSku),
            'lines' => $this->lines(),
            'inUse' => $oeeSku->isInUse(),
        ]);
    }

    /**
     * Update the specified catalog entry.
     */
    public function update(UpdateSkuRequest $request, Team $current_team, OeeSku $oeeSku): RedirectResponse
    {
        Gate::authorize('update', [$oeeSku, $current_team]);

        $oeeSku->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto actualizado.']);

        return to_route('oee.admin.skus.index', $current_team);
    }

    /**
     * Remove the specified catalog entry.
     */
    public function destroy(Team $current_team, OeeSku $oeeSku): RedirectResponse
    {
        Gate::authorize('delete', [$oeeSku, $current_team]);

        if ($oeeSku->isInUse()) {
            return back()->withErrors([
                'sku' => 'Este producto ya se usó en un turno. Desactívalo en lugar de eliminarlo.',
            ]);
        }

        $oeeSku->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto eliminado.']);

        return to_route('oee.admin.skus.index', $current_team);
    }

    /**
     * @return array<int, string>
     */
    private function lines(): array
    {
        /** @var array<int, string> $lines */
        $lines = require database_path('data/oee_lines.php');

        return $lines;
    }
}
