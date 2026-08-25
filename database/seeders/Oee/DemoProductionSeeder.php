<?php

namespace Database\Seeders\Oee;

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeStopDetail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fills a team with a month of plausible bottling production.
 *
 * Downtime is drawn first and the output is derived from it, so every hour is
 * internally consistent: what the line failed to produce is exactly what the
 * recorded stops explain.
 */
class DemoProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Days of history to generate, counting back from today.
     */
    private const DAYS = 30;

    /**
     * Relative frequency of each stop code, taken from the plant's own history.
     *
     * @var array<string, int>
     */
    private const STOP_WEIGHTS = [
        'J36' => 14, 'J38' => 12, 'J50' => 11, 'J110' => 10,
        'T29' => 9, 'FL1' => 7, 'E6' => 6, 'J3' => 5,
        'J34' => 5, 'ST11' => 4, 'J53' => 4, 'A1' => 3,
        'B6' => 3, 'H33' => 3, 'J32' => 3, 'FL12' => 2,
        'E11' => 2, 'DN3' => 2, 'TJ64' => 1,
    ];

    /**
     * Longest stop each family tends to cause, in minutes.
     *
     * @var array<string, array{int, int}>
     */
    private const DURATION_BY_FAMILY = [
        'EQ' => [4, 35],
        'OPD' => [2, 12],
        'OR' => [3, 20],
        'PD' => [5, 25],
        'QD' => [2, 10],
        'RD' => [2, 8],
    ];

    /**
     * People who show up as engineer and operator on the shift sheets.
     *
     * @var array<int, string>
     */
    private const ENGINEERS = ['R. Quispe', 'M. Salazar', 'J. Ccahuana', 'L. Ramírez'];

    private const OPERATORS = ['A. Huamán', 'C. Torres', 'D. Flores', 'S. Mamani', 'P. Vargas'];

    /**
     * Seed a month of production for the given team.
     */
    public function run(Team $team, ?User $creator = null): void
    {
        /** @var Collection<string, CodStop> $catalog */
        $catalog = CodStop::query()->get()->keyBy('codigo');

        if ($catalog->isEmpty()) {
            throw new RuntimeException(
                'El catálogo de paradas está vacío. Ejecuta CodStopSeeder antes de sembrar producción.',
            );
        }

        $skus = OeeSku::query()->active()->get()->keyBy('sku');

        if ($skus->isEmpty()) {
            throw new RuntimeException(
                'El catálogo de SKU está vacío. Ejecuta SkuSeeder antes de sembrar producción.',
            );
        }

        $today = CarbonImmutable::today();
        $orderNumber = 100;

        // One transaction keeps thousands of small inserts from crawling.
        DB::transaction(function () use ($team, $creator, $catalog, $skus, $today, &$orderNumber) {
            for ($daysAgo = self::DAYS; $daysAgo >= 0; $daysAgo--) {
                $date = $today->subDays($daysAgo);

                // Sundays are down for maintenance, so nothing is recorded.
                if ($date->isSunday()) {
                    continue;
                }

                foreach ($skus as $sku) {
                    if ($sku->linea === '') {
                        continue;
                    }

                    foreach (Shift::cases() as $shift) {
                        $this->seedShift(
                            $team,
                            $creator,
                            $date,
                            $sku->linea,
                            $sku,
                            $shift,
                            $catalog,
                            $orderNumber++,
                            // The latest day stays open so the UI shows a shift in progress.
                            isOpen: $daysAgo === 0,
                        );
                    }
                }
            }
        });
    }

    /**
     * Record one line's shift, hour by hour.
     *
     * @param  Collection<string, CodStop>  $catalog
     */
    private function seedShift(
        Team $team,
        ?User $creator,
        CarbonImmutable $date,
        string $line,
        OeeSku $sku,
        Shift $shift,
        Collection $catalog,
        int $orderNumber,
        bool $isOpen,
    ): void {
        $production = OeeProduction::query()->create([
            'team_id' => $team->id,
            'created_by' => $creator?->id,
            'fecha' => $date->toDateString(),
            'turno' => $shift,
            'linea' => $line,
            'op' => $date->format('Y').str_pad((string) $orderNumber, 6, '0', STR_PAD_LEFT),
            'ingeniero' => self::ENGINEERS[array_rand(self::ENGINEERS)],
            'operador' => self::OPERATORS[array_rand(self::OPERATORS)],
            'sku' => $sku->sku,
            'descripcion' => $sku->descripcion,
            'formato' => $sku->formato,
            'marca' => $sku->marca,
            'sabor' => $sku->sabor,
            'pallets_por_hora' => $sku->pallets_por_hora,
            'bph' => $sku->bph,
            'closed_at' => $isOpen ? null : $date->setTime(23, 0),
        ]);

        // Now and then a line runs out of programme before the shift ends.
        $unscheduledFrom = random_int(1, 100) <= 8 ? random_int(9, 11) : null;

        foreach ($shift->hourRanges() as $index => $range) {
            $this->seedHour(
                $production,
                $index,
                $range,
                (float) $sku->pallets_por_hora,
                $catalog,
                $isOpen,
                $unscheduledFrom,
            );
        }
    }

    /**
     * Record one hour and the stops that explain its shortfall.
     *
     * @param  Collection<string, CodStop>  $catalog
     */
    private function seedHour(
        OeeProduction $production,
        int $index,
        string $range,
        float $target,
        Collection $catalog,
        bool $shiftIsOpen,
        ?int $unscheduledFrom,
    ): void {
        $isUnscheduled = $unscheduledFrom !== null && $index >= $unscheduledFrom;

        $stops = $isUnscheduled ? $this->unscheduledHour() : $this->drawStops($index, $catalog);
        $downtime = array_sum(array_column($stops, 'minutos'));

        // Output follows from the minutes the line actually ran.
        $produced = round($target * (OeeHourDetail::MINUTES_PER_HOUR - $downtime) / OeeHourDetail::MINUTES_PER_HOUR, 2);

        // Some hours end short with no stop to account for it, which is exactly
        // what the pending minutes indicator exists to surface.
        if (! $isUnscheduled && random_int(1, 100) <= 12) {
            $produced = round($produced * (1 - random_int(2, 5) / 100), 2);
        }

        // An open shift is still being filled in, so its later hours are blank.
        $isPending = $shiftIsOpen && $index >= 6;

        $hour = OeeHourDetail::query()->create([
            'oee_production_id' => $production->id,
            'hour_index' => $index,
            'hour_range' => $range,
            'estimado' => $target,
            'producido' => $isPending ? null : $produced,
            'closed' => ! $isPending,
            'closed_at' => $isPending ? null : now(),
            'comment_mnf' => $downtime > 20 ? 'Se escaló a mantenimiento durante la hora.' : null,
        ]);

        if ($isPending) {
            return;
        }

        foreach ($stops as $stop) {
            $code = $catalog[$stop['codigo']];

            OeeStopDetail::query()->create([
                'oee_hour_detail_id' => $hour->id,
                'cod_stop_id' => $code->id,
                'client_uuid' => (string) Str::uuid(),
                'codigo' => $code->codigo,
                'tipo' => $code->tipo_parada,
                'descripcion' => $code->detalle,
                'tiempo_minutos' => $stop['minutos'],
                'frecuencia' => $stop['frecuencia'],
                'registered_at' => $production->fecha->setTime(8, 0)->addHours($index),
            ]);
        }
    }

    /**
     * An hour the line had no programme for.
     *
     * This is not a loss: it shrinks the window the OEE is measured over.
     *
     * @return array<int, array{codigo: string, minutos: float, frecuencia: int}>
     */
    private function unscheduledHour(): array
    {
        return [[
            'codigo' => 'TJ64',
            'minutos' => (float) OeeHourDetail::MINUTES_PER_HOUR,
            'frecuencia' => 1,
        ]];
    }

    /**
     * Draw the stops for one hour.
     *
     * @param  Collection<string, CodStop>  $catalog
     * @return array<int, array{codigo: string, minutos: float, frecuencia: int}>
     */
    private function drawStops(int $hourIndex, Collection $catalog): array
    {
        $stops = [];

        // The first hour of a shift is the start-up, which always costs time.
        $count = $hourIndex === 0 ? 1 : $this->drawStopCount();

        for ($i = 0; $i < $count; $i++) {
            $code = $hourIndex === 0 && $i === 0 ? 'J36' : $this->drawCode();
            $family = $catalog->get($code)?->tipo_parada->value ?? 'EQ';
            [$shortest, $longest] = self::DURATION_BY_FAMILY[$family] ?? [2, 10];

            $stops[] = [
                'codigo' => $code,
                'minutos' => (float) random_int($shortest, $longest),
                'frecuencia' => random_int(1, 100) > 85 ? 2 : 1,
            ];
        }

        return $this->cappedToTheHour($stops);
    }

    /**
     * Decide how many stops an hour suffers.
     *
     * Most hours run clean; the tail is what the Pareto is meant to surface.
     */
    private function drawStopCount(): int
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 45 => 0,
            $roll <= 80 => 1,
            $roll <= 95 => 2,
            default => 3,
        };
    }

    /**
     * Pick a stop code, favouring the ones that fail most often.
     */
    private function drawCode(): string
    {
        $target = random_int(1, array_sum(self::STOP_WEIGHTS));

        foreach (self::STOP_WEIGHTS as $code => $weight) {
            $target -= $weight;

            if ($target <= 0) {
                return $code;
            }
        }

        return array_key_first(self::STOP_WEIGHTS);
    }

    /**
     * Trim the drawn stops so they never claim more than the hour holds.
     *
     * @param  array<int, array{codigo: string, minutos: float, frecuencia: int}>  $stops
     * @return array<int, array{codigo: string, minutos: float, frecuencia: int}>
     */
    private function cappedToTheHour(array $stops): array
    {
        $budget = (float) OeeHourDetail::MINUTES_PER_HOUR;
        $kept = [];

        foreach ($stops as $stop) {
            if ($budget <= 0) {
                break;
            }

            $stop['minutos'] = min($stop['minutos'], $budget);
            $budget -= $stop['minutos'];
            $kept[] = $stop;
        }

        return $kept;
    }
}
