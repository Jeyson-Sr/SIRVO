<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;

/**
 * Shapes a recorded shift as the recording form's draft, padding empty hours.
 */
class PresentRecordingDraft
{
    /**
     * @return array{production: array<string, mixed>, hours: array<int, array<string, mixed>>}
     */
    public function handle(OeeProduction $production): array
    {
        $production->loadMissing('hours.stops');

        $ranges = $production->turno->hourRanges();
        $hours = $production->hours->map(fn (OeeHourDetail $hour): array => [
            'hour_index' => $hour->hour_index,
            'hour_range' => $hour->hour_range,
            'duration_minutes' => (float) $hour->duration_minutes,
            'sku' => $hour->sku ?? '',
            'formato' => $hour->formato ?? '',
            'pallets_por_hora' => (string) (float) $hour->pallets_por_hora,
            'bph' => (string) (float) $hour->bph,
            'estimado' => (string) (float) $hour->estimado,
            'producido' => $hour->producido === null ? '' : (string) (float) $hour->producido,
            'closed' => $hour->closed,
            'comments' => [
                'mnf' => $hour->comment_mnf ?? '',
                'mantto' => $hour->comment_mantto ?? '',
                'calidad' => $hour->comment_calidad ?? '',
            ],
            'stops' => $hour->stops->map(fn (OeeStopDetail $stop): array => [
                'client_uuid' => $stop->client_uuid,
                'codigo' => $stop->codigo,
                'tipo' => $stop->tipo->value,
                'descripcion' => $stop->descripcion ?? '',
                'comentario' => $stop->comentario ?? '',
                'tiempo_minutos' => (float) $stop->tiempo_minutos,
                'frecuencia' => $stop->frecuencia,
                'continua' => $stop->continua,
                'registered_at' => $stop->registered_at->toISOString(),
            ])->all(),
        ])->all();

        $nextIndex = count($hours);

        while ($nextIndex < count($ranges)) {
            $hours[] = [
                'hour_index' => $nextIndex,
                'hour_range' => $ranges[$nextIndex],
                'duration_minutes' => OeeHourDetail::MINUTES_PER_HOUR,
                'sku' => $production->sku ?? '',
                'formato' => $production->formato ?? '',
                'pallets_por_hora' => (string) (float) $production->pallets_por_hora,
                'bph' => (string) (float) $production->bph,
                'estimado' => (string) (float) $production->pallets_por_hora,
                'producido' => '',
                'closed' => false,
                'comments' => ['mnf' => '', 'mantto' => '', 'calidad' => ''],
                'stops' => [],
            ];
            $nextIndex++;
        }

        return [
            'production' => [
                'fecha' => $production->fecha->toDateString(),
                'turno' => $production->turno->value,
                'linea' => $production->linea,
                'op' => $production->op,
                'ingeniero' => $production->ingeniero ?? '',
                'operador' => $production->operador ?? '',
                'sku' => $production->sku ?? '',
                'descripcion' => $production->descripcion ?? '',
                'formato' => $production->formato ?? '',
                'marca' => $production->marca ?? '',
                'sabor' => $production->sabor ?? '',
                'pallets_por_hora' => (string) (float) $production->pallets_por_hora,
                'bph' => (string) (float) $production->bph,
            ],
            'hours' => $hours,
        ];
    }
}
