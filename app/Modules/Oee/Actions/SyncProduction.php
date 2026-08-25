<?php

namespace App\Modules\Oee\Actions;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Data\ProductionOrder;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persists a production run submitted from the shift recording screen.
 *
 * The payload is treated as the desired state of the run: hours and stops are
 * matched on their natural keys and updated in place, and only the stops the
 * operator actually removed get deleted. A retried or duplicated submission
 * therefore lands on the same rows instead of recreating them.
 */
class SyncProduction
{
    /**
     * @param  array{
     *     production: array<string, mixed>,
     *     hours: array<int, array<string, mixed>>,
     * }  $payload
     *
     * @throws ValidationException when the shift has already been signed off.
     */
    public function handle(Team $team, User $user, array $payload, ?OeeProduction $existing = null): OeeProduction
    {
        $productionAttributes = $payload['production'];

        $productionOrder = ProductionOrder::fromInput(
            $productionAttributes['op'],
            $productionAttributes['fecha'],
        );

        // Resolved before the transaction opens so the catalog lookup does not
        // hold the production row lock any longer than necessary.
        $stopCatalog = $this->catalogFor($payload['hours']);

        return DB::transaction(function () use ($team, $user, $productionAttributes, $productionOrder, $payload, $stopCatalog, $existing) {
            $production = $this->lockProduction($team, $user, $productionAttributes, $productionOrder, $existing);

            if ($production->isClosed() && ! $user->hasTeamPermission($team, TeamPermission::ReopenProduction)) {
                throw ValidationException::withMessages([
                    'production' => 'El turno ya fue cerrado y no admite más cambios.',
                ]);
            }

            foreach ($payload['hours'] as $hourAttributes) {
                $this->syncHour($production, $hourAttributes, $stopCatalog);
            }

            return $production->load('hours.stops');
        });
    }

    /**
     * Resolve the production run for the submitted natural key and lock it.
     *
     * `createOrFirst` absorbs the unique-constraint race between two operators
     * opening the same run, and the row lock serialises concurrent syncs so the
     * last writer cannot silently discard the other's hours.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function lockProduction(
        Team $team,
        User $user,
        array $attributes,
        ProductionOrder $productionOrder,
        ?OeeProduction $existing = null,
    ): OeeProduction {
        if ($existing instanceof OeeProduction) {
            $lockedProduction = OeeProduction::whereKey($existing->id)
                ->lockForUpdate()
                ->firstOrFail();
        } else {
            $naturalKey = [
                'team_id' => $team->id,
                'fecha' => $attributes['fecha'],
                'turno' => $attributes['turno'],
                'linea' => $attributes['linea'],
                'op' => $productionOrder->value,
            ];

            $existingOrCreated = OeeProduction::createOrFirst($naturalKey, [
                'created_by' => $user->id,
            ]);

            $lockedProduction = OeeProduction::whereKey($existingOrCreated->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $lockedProduction->fill([
            'ingeniero' => $user->name,
            'operador' => $attributes['operador'] ?? null,
            'sku' => $attributes['sku'] ?? null,
            'descripcion' => $attributes['descripcion'] ?? null,
            'formato' => $attributes['formato'] ?? null,
            'marca' => $attributes['marca'] ?? null,
            'sabor' => $attributes['sabor'] ?? null,
            'pallets_por_hora' => $attributes['pallets_por_hora'] ?? 0,
            'bph' => $attributes['bph'] ?? 0,
        ])->save();

        return $lockedProduction;
    }

    /**
     * Bring one hour slot and its stops in line with the submitted state.
     *
     * @param  array<string, mixed>  $attributes
     * @param  Collection<string, CodStop>  $stopCatalog
     */
    private function syncHour(OeeProduction $production, array $attributes, Collection $stopCatalog): void
    {
        /** @var OeeHourDetail $hour */
        $hour = $production->hours()->firstOrNew(['hour_index' => $attributes['hour_index']]);

        // Closing is one-way: an hour already signed off stays closed even if the
        // client replays an older, still-open version of it.
        $isClosed = $hour->closed || (bool) ($attributes['closed'] ?? false);

        $hourChanges = [
            'hour_range' => $attributes['hour_range'],
            'duration_minutes' => $attributes['duration_minutes'] ?? OeeHourDetail::MINUTES_PER_HOUR,
            'sku' => $attributes['sku'] ?? $production->sku,
            'formato' => $attributes['formato'] ?? $production->formato,
            'pallets_por_hora' => $attributes['pallets_por_hora'] ?? $production->pallets_por_hora,
            'bph' => $attributes['bph'] ?? $production->bph,
            'estimado' => $attributes['estimado'] ?? 0,
            'producido' => $attributes['producido'] ?? null,
            'closed' => $isClosed,
            'comment_mnf' => $attributes['comments']['mnf'] ?? null,
            'comment_mantto' => $attributes['comments']['mantto'] ?? null,
            'comment_calidad' => $attributes['comments']['calidad'] ?? null,
        ];

        if ($isClosed && $hour->closed_at === null) {
            $hourChanges['closed_at'] = now();
        }

        $hour->fill($hourChanges)->save();

        $this->syncStops($hour, $attributes['stops'] ?? [], $stopCatalog);
    }

    /**
     * Bring the stops of an hour in line with the submitted state.
     *
     * The loss family and default description always come from the catalog, so a
     * client cannot choose which OEE bucket its downtime is charged to.
     *
     * @param  array<int, array<string, mixed>>  $submittedStops
     * @param  Collection<string, CodStop>  $stopCatalog
     */
    private function syncStops(OeeHourDetail $hour, array $submittedStops, Collection $stopCatalog): void
    {
        $retainedStopIds = [];

        foreach ($submittedStops as $stopAttributes) {
            $catalogEntry = $stopCatalog->get((string) $stopAttributes['codigo']);

            if (! $catalogEntry instanceof CodStop) {
                continue;
            }

            $isContinuation = (bool) ($stopAttributes['continua'] ?? false);

            $stop = $hour->stops()->updateOrCreate([
                'client_uuid' => $stopAttributes['client_uuid'],
            ], [
                'cod_stop_id' => $catalogEntry->id,
                'codigo' => $catalogEntry->codigo,
                'tipo' => $catalogEntry->tipo_parada,
                'descripcion' => $stopAttributes['descripcion'] ?? $catalogEntry->detalle,
                'comentario' => $stopAttributes['comentario'] ?? null,
                'tiempo_minutos' => $stopAttributes['tiempo_minutos'],
                'continua' => $isContinuation,
                'frecuencia' => $isContinuation ? 0 : ($stopAttributes['frecuencia'] ?? 1),
                'registered_at' => $stopAttributes['registered_at'] ?? now(),
            ]);

            $retainedStopIds[] = $stop->id;
        }

        // Anything the operator deleted on the client disappears here, and only
        // that: the stops still present keep their identity and timestamps.
        $hour->stops()->whereNotIn('id', $retainedStopIds)->delete();
    }

    /**
     * Load every catalog entry referenced by the payload in one query.
     *
     * @param  array<int, array<string, mixed>>  $hours
     * @return Collection<string, CodStop>
     */
    private function catalogFor(array $hours): Collection
    {
        $referencedCodes = [];

        foreach ($hours as $hourAttributes) {
            foreach ($hourAttributes['stops'] ?? [] as $stopAttributes) {
                if (is_array($stopAttributes) && ! blank($stopAttributes['codigo'] ?? null)) {
                    $referencedCodes[] = (string) $stopAttributes['codigo'];
                }
            }
        }

        $referencedCodes = array_values(array_unique($referencedCodes));

        if ($referencedCodes === []) {
            return new Collection;
        }

        return CodStop::query()
            ->whereIn('codigo', $referencedCodes)
            ->get()
            ->keyBy('codigo');
    }
}
