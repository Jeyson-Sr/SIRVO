<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeSku;

/**
 * Shapes a finished good for the admin list and the edit form.
 */
class PresentSku
{
    /**
     * @return array{id: int, sku: string, linea: string, descripcion: string, formato: string|null, marca: string|null, sabor: string|null, palletsPorHora: float, bph: float, activo: bool}
     */
    public function listItem(OeeSku $sku): array
    {
        return [
            'id' => $sku->id,
            'sku' => $sku->sku,
            'linea' => $sku->linea,
            'descripcion' => $sku->descripcion,
            'formato' => $sku->formato,
            'marca' => $sku->marca,
            'sabor' => $sku->sabor,
            'palletsPorHora' => (float) $sku->pallets_por_hora,
            'bph' => (float) $sku->bph,
            'activo' => $sku->activo,
        ];
    }

    /**
     * @return array{id: int, sku: string, linea: string, descripcion: string, formato: string, marca: string, sabor: string, pallets_por_hora: string, bph: string, activo: bool}
     */
    public function form(OeeSku $sku): array
    {
        return [
            'id' => $sku->id,
            'sku' => $sku->sku,
            'linea' => $sku->linea,
            'descripcion' => $sku->descripcion,
            'formato' => $sku->formato ?? '',
            'marca' => $sku->marca ?? '',
            'sabor' => $sku->sabor ?? '',
            'pallets_por_hora' => (string) (float) $sku->pallets_por_hora,
            'bph' => (string) (float) $sku->bph,
            'activo' => $sku->activo,
        ];
    }
}
