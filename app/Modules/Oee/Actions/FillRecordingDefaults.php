<?php

namespace App\Modules\Oee\Actions;

use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeSku;

/**
 * Completes a recording payload from the SKU and stop catalogs.
 *
 * The operator only has to type what the floor actually changes: the SKU, the
 * bottles produced in the hour, and the stop code. Everything else — product
 * sheet, hourly target, stop family and unexplained minutes — is derived.
 */
class FillRecordingDefaults
{
    /**
     * @param  array{production?: array<string, mixed>, hours?: array<int, mixed>}  $payload
     * @return array{production: array<string, mixed>, hours: array<int, mixed>}
     */
    public function handle(array $payload): array
    {
        $production = $this->fillProduct($payload['production'] ?? []);
        $hours = $this->fillHours($payload['hours'] ?? [], $production);

        return [
            'production' => $production,
            'hours' => $hours,
        ];
    }

    /**
     * @param  array<string, mixed>  $production
     * @return array<string, mixed>
     */
    private function fillProduct(array $production): array
    {
        $skuCode = trim((string) ($production['sku'] ?? ''));

        if ($skuCode === '') {
            return $production;
        }

        $sku = OeeSku::query()->active()->where('sku', $skuCode)->first();

        if (! $sku instanceof OeeSku) {
            return $production;
        }

        foreach (['descripcion', 'formato', 'marca', 'sabor', 'pallets_por_hora', 'bph'] as $field) {
            if (blank($production[$field] ?? null)) {
                $production[$field] = $sku->{$field};
            }
        }

        return $production;
    }

    /**
     * @param  array<int, mixed>  $hours
     * @param  array<string, mixed>  $production
     * @return array<int, mixed>
     */
    private function fillHours(array $hours, array $production): array
    {
        $split = new SplitHourAt;

        return array_map(function (mixed $hour) use ($production, $split): mixed {
            if (! is_array($hour)) {
                return $hour;
            }

            $duration = blank($hour['duration_minutes'] ?? null)
                ? (float) OeeHourDetail::MINUTES_PER_HOUR
                : (float) $hour['duration_minutes'];

            $hour['duration_minutes'] = $duration;

            foreach (['sku', 'formato', 'pallets_por_hora', 'bph'] as $field) {
                if (blank($hour[$field] ?? null) && ! blank($production[$field] ?? null)) {
                    $hour[$field] = $production[$field];
                }
            }

            $target = $hour['pallets_por_hora'] ?? $production['pallets_por_hora'] ?? null;

            if (blank($hour['estimado'] ?? null) && ! blank($target)) {
                $hour['estimado'] = $split->target((float) $target, $duration);
            }

            if (is_array($hour['stops'] ?? null)) {
                $hour['stops'] = $this->fillStopMinutes($hour);
            }

            return $hour;
        }, $hours);
    }

    /**
     * Assign unexplained minutes to any stop that did not declare a duration.
     *
     * @param  array<string, mixed>  $hour
     * @return array<int, mixed>
     */
    private function fillStopMinutes(array $hour): array
    {
        $estimated = (float) ($hour['estimado'] ?? 0);
        $produced = blank($hour['producido'] ?? null) ? null : (float) $hour['producido'];
        $remaining = OeeHourDetail::minutesToJustify(
            $estimated,
            $produced,
            blank($hour['duration_minutes'] ?? null)
                ? (float) OeeHourDetail::MINUTES_PER_HOUR
                : (float) $hour['duration_minutes'],
        );

        foreach ($hour['stops'] as $index => $stop) {
            if (! is_array($stop)) {
                continue;
            }

            if (! blank($stop['tiempo_minutos'] ?? null)) {
                $remaining = max(0, $remaining - (float) $stop['tiempo_minutos']);

                continue;
            }

            $hour['stops'][$index]['tiempo_minutos'] = round($remaining, 2);
            $remaining = 0;
        }

        return $hour['stops'];
    }
}
