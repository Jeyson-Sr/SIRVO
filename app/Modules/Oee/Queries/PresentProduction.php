<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;

/**
 * Shapes a recorded shift and its hours for the detail screen.
 */
class PresentProduction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(OeeProduction $production): array
    {
        $production->load(['hours.stops', 'creator']);

        return [
            'id' => $production->id,
            'fecha' => $production->fecha->toDateString(),
            'turno' => $production->turno->value,
            'turnoLabel' => $production->turno->label(),
            'linea' => $production->linea,
            'op' => $production->op,
            'ingeniero' => $production->ingeniero,
            'operador' => $production->operador,
            'sku' => $production->sku,
            'descripcion' => $production->descripcion,
            'formato' => $production->formato,
            'marca' => $production->marca,
            'sabor' => $production->sabor,
            'palletsPorHora' => (float) $production->pallets_por_hora,
            'bph' => (float) $production->bph,
            'isClosed' => $production->isClosed(),
            'createdBy' => $production->creator?->name,
            'hours' => $production->hours->map(fn (OeeHourDetail $hour) => $this->hour($hour))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hour(OeeHourDetail $hour): array
    {
        return [
            'id' => $hour->id,
            'hourIndex' => $hour->hour_index,
            'hourRange' => $hour->hour_range,
            'durationMinutes' => (float) $hour->duration_minutes,
            'sku' => $hour->sku,
            'estimado' => (float) $hour->estimado,
            'producido' => $hour->producido === null ? null : (float) $hour->producido,
            'status' => $hour->status->value,
            'statusLabel' => $hour->status->label(),
            'minutosAJustificar' => $hour->minutos_a_justificar,
            'minutosJustificados' => $hour->minutos_justificados,
            'minutosPendientes' => $hour->minutos_pendientes,
            'closed' => $hour->closed,
            'comments' => [
                'mnf' => $hour->comment_mnf,
                'mantto' => $hour->comment_mantto,
                'calidad' => $hour->comment_calidad,
            ],
            'stops' => $hour->stops->map(fn (OeeStopDetail $stop) => [
                'id' => $stop->id,
                'clientUuid' => $stop->client_uuid,
                'codigo' => $stop->codigo,
                'tipo' => $stop->tipo->value,
                'tipoLabel' => $stop->tipo->label(),
                'descripcion' => $stop->descripcion,
                'comentario' => $stop->comentario,
                'tiempoMinutos' => (float) $stop->tiempo_minutos,
                'frecuencia' => $stop->frecuencia,
                'continua' => $stop->continua,
                'registeredAt' => $stop->registered_at->toISOString(),
            ])->all(),
        ];
    }
}
