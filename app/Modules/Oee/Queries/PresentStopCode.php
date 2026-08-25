<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\CodStop;

/**
 * Shapes a catalog entry for the admin list and the edit form.
 */
class PresentStopCode
{
    /**
     * @return array{id: int, codigo: string, detalle: string, tipo: string, tipoLabel: string, tipoDescription: string, categoria: string|null, causa: string|null, recursoAfectado: string|null, esTetraPak: bool, activo: bool}
     */
    public function listItem(CodStop $code): array
    {
        return [
            'id' => $code->id,
            'codigo' => $code->codigo,
            'detalle' => $code->detalle,
            'tipo' => $code->tipo_parada->value,
            'tipoLabel' => $code->tipo_parada->label(),
            'tipoDescription' => $code->tipo_parada->description(),
            'categoria' => $code->categoria,
            'causa' => $code->causa,
            'recursoAfectado' => $code->recurso_afectado,
            'esTetraPak' => $code->es_tetra_pak,
            'activo' => $code->activo,
        ];
    }

    /**
     * @return array{id: int, codigo: string, detalle: string, tipo_parada: string, categoria: string, causa: string, recurso_afectado: string, familia_oee: string, es_tetra_pak: bool, activo: bool}
     */
    public function form(CodStop $code): array
    {
        return [
            'id' => $code->id,
            'codigo' => $code->codigo,
            'detalle' => $code->detalle,
            'tipo_parada' => $code->tipo_parada->value,
            'categoria' => $code->categoria ?? '',
            'causa' => $code->causa ?? '',
            'recurso_afectado' => $code->recurso_afectado ?? '',
            'familia_oee' => $code->familia_oee ?? '',
            'es_tetra_pak' => $code->es_tetra_pak,
            'activo' => $code->activo,
        ];
    }
}
