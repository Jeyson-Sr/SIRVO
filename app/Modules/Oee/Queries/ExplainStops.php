<?php

namespace App\Modules\Oee\Queries;

use App\Models\Team;
use App\Modules\Oee\Concerns\FiltersProductionQueries;
use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopRanking;
use App\Modules\Oee\Enums\StopType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rank stop codes and attach the comments, products and lines behind each one.
 */
class ExplainStops
{
    use FiltersProductionQueries;

    public function __construct(private RankStops $rankStops) {}

    /**
     * @return array<int, array{
     *     codigo: string,
     *     descripcion: string|null,
     *     tipo: string,
     *     tipoLabel: string,
     *     totalMinutos: float,
     *     totalFrecuencia: int,
     *     porcentaje: float,
     *     porcentajeAcumulado: float,
     *     comentarios: array<int, array{comentario: string, fecha: string, hora: string, totalMinutos: float, totalFrecuencia: int}>,
     *     productos: array<int, array{sku: string, producto: string, totalMinutos: float, totalFrecuencia: int}>,
     *     lineas: array<int, array{linea: string, totalMinutos: float, totalFrecuencia: int}>,
     *     ocurrencias: array<int, array{sku: string, producto: string, linea: string, totalMinutos: float, totalFrecuencia: int}>,
     * }>
     */
    public function handle(
        Team $team,
        ProductionFilters $filters,
        StopRanking $sortBy = StopRanking::Minutes,
        int $limit = RankStops::DEFAULT_LIMIT,
    ): array {
        $ranking = $this->rankStops->handle($team, $filters, $sortBy, $limit);

        if ($ranking === []) {
            return [];
        }

        $eventsByCode = $this->events($team, $filters, array_column($ranking, 'codigo'));

        return array_map(function (array $rankedStop) use ($eventsByCode): array {
            $events = $eventsByCode->get($rankedStop['codigo'], collect());

            return [
                ...$rankedStop,
                'comentarios' => $this->commentEvents($events),
                'productos' => $this->groupProducts($events),
                'lineas' => $this->groupLines($events),
                'ocurrencias' => $this->groupOccurrences($events),
            ];
        }, $ranking);
    }

    /**
     * @param  array<int, string>  $codes
     * @return Collection<string, Collection<int, object>>
     */
    private function events(Team $team, ProductionFilters $filters, array $codes): Collection
    {
        return $this->applyProductionFilters(
            DB::table('oee_stop_details as s')
                ->join('oee_hour_details as h', 'h.id', '=', 's.oee_hour_detail_id')
                ->join('oee_productions as p', 'p.id', '=', 'h.oee_production_id'),
            $team,
            $filters,
        )->when(
            $filters->componente,
            fn (Builder $query, StopType $stopType) => $query->where('s.tipo', $stopType->value),
        )
            ->whereIn('s.codigo', $codes)
            ->select([
                's.codigo',
                's.comentario',
                's.tiempo_minutos',
                's.frecuencia',
                's.registered_at',
                'p.fecha',
                'p.linea',
                'p.descripcion as producto',
                'p.sku as production_sku',
                'h.sku as hour_sku',
                'h.hour_range',
            ])
            ->get()
            ->groupBy(fn (object $event): string => (string) $event->codigo);
    }

    /**
     * @param  Collection<int, object>  $events
     * @return array<int, array{comentario: string, fecha: string, hora: string, totalMinutos: float, totalFrecuencia: int}>
     */
    private function commentEvents(Collection $events): array
    {
        return $events
            ->sortByDesc(fn (object $event): int => $this->happenedAt($event)->getTimestamp())
            ->map(fn (object $event): array => [
                'comentario' => $this->commentOf($event),
                'fecha' => $this->dateOf($event),
                'hora' => $this->hourOf($event),
                'totalMinutos' => round((float) $event->tiempo_minutos, 2),
                'totalFrecuencia' => (int) $event->frecuencia,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $events
     * @return array<int, array{sku: string, producto: string, totalMinutos: float, totalFrecuencia: int}>
     */
    private function groupProducts(Collection $events): array
    {
        return $this->aggregate($events, fn (object $event): string => $this->skuOf($event))
            ->map(function (array $group) use ($events): array {
                $sample = $events->first(
                    fn (object $event): bool => $this->skuOf($event) === $group['key'],
                );

                return [
                    'sku' => $group['key'],
                    'producto' => $this->productOf($sample),
                    'totalMinutos' => $group['totalMinutos'],
                    'totalFrecuencia' => $group['totalFrecuencia'],
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, object>  $events
     * @return array<int, array{linea: string, totalMinutos: float, totalFrecuencia: int}>
     */
    private function groupLines(Collection $events): array
    {
        return $this->aggregate($events, fn (object $event): string => $this->lineOf($event))
            ->map(fn (array $group): array => [
                'linea' => $group['key'],
                'totalMinutos' => $group['totalMinutos'],
                'totalFrecuencia' => $group['totalFrecuencia'],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, object>  $events
     * @return array<int, array{sku: string, producto: string, linea: string, totalMinutos: float, totalFrecuencia: int}>
     */
    private function groupOccurrences(Collection $events): array
    {
        return $this->aggregate(
            $events,
            fn (object $event): string => $this->skuOf($event).'|'.$this->lineOf($event),
        )->map(function (array $group) use ($events): array {
            $sample = $events->first(function (object $event) use ($group): bool {
                return $this->skuOf($event).'|'.$this->lineOf($event) === $group['key'];
            });

            return [
                'sku' => $this->skuOf($sample),
                'producto' => $this->productOf($sample),
                'linea' => $this->lineOf($sample),
                'totalMinutos' => $group['totalMinutos'],
                'totalFrecuencia' => $group['totalFrecuencia'],
            ];
        })->all();
    }

    /**
     * @param  Collection<int, object>  $events
     * @param  callable(object): string  $groupBy
     * @return Collection<int, array{key: string, totalMinutos: float, totalFrecuencia: int}>
     */
    private function aggregate(Collection $events, callable $groupBy): Collection
    {
        return $events
            ->groupBy($groupBy)
            ->map(fn (Collection $group, string $key): array => [
                'key' => $key,
                'totalMinutos' => round((float) $group->sum(fn (object $event): float => (float) $event->tiempo_minutos), 2),
                'totalFrecuencia' => (int) $group->sum(fn (object $event): int => (int) $event->frecuencia),
            ])
            ->sortBy([
                ['totalMinutos', 'desc'],
                ['key', 'asc'],
            ])
            ->values();
    }

    private function commentOf(object $event): string
    {
        $comment = Str::squish((string) $event->comentario);

        return $comment === '' ? '—' : $comment;
    }

    private function dateOf(object $event): string
    {
        $fecha = $event->fecha ?? null;

        if ($fecha === null || $fecha === '') {
            return '—';
        }

        return Carbon::parse($fecha)->toDateString();
    }

    private function hourOf(object $event): string
    {
        $range = Str::squish((string) ($event->hour_range ?? ''));

        if ($range !== '') {
            return $range;
        }

        $registeredAt = $event->registered_at ?? null;

        if ($registeredAt === null || $registeredAt === '') {
            return '—';
        }

        return Carbon::parse($registeredAt)->format('H:i');
    }

    private function happenedAt(object $event): Carbon
    {
        $registeredAt = $event->registered_at ?? null;

        if ($registeredAt !== null && $registeredAt !== '') {
            return Carbon::parse($registeredAt);
        }

        return Carbon::parse($this->dateOf($event));
    }

    private function skuOf(?object $event): string
    {
        if ($event === null) {
            return '—';
        }

        $sku = Str::squish((string) ($event->hour_sku ?: $event->production_sku));

        return $sku === '' ? '—' : $sku;
    }

    private function productOf(?object $event): string
    {
        if ($event === null) {
            return '—';
        }

        $product = Str::squish((string) $event->producto);

        return $product === '' ? '—' : $product;
    }

    private function lineOf(?object $event): string
    {
        if ($event === null) {
            return '—';
        }

        $line = Str::squish((string) $event->linea);

        return $line === '' ? '—' : $line;
    }
}
