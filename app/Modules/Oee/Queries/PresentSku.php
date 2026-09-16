<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeSkuBphChange;

/**
 * Shapes a finished good for the admin list and the edit form.
 */
class PresentSku
{
    /**
     * @return array{id: int, sku: string, linea: string, descripcion: string, formato: string|null, marca: string|null, sabor: string|null, um: int, palletsPorHora: float, bph: float, compania: string|null, mercado: string|null, paqPallet: int, activo: bool}
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
            'um' => (int) $sku->um,
            'palletsPorHora' => (float) $sku->pallets_por_hora,
            'bph' => (float) $sku->bph,
            'compania' => $sku->compania,
            'mercado' => $sku->mercado,
            'paqPallet' => (int) $sku->paq_pallet,
            'activo' => $sku->activo,
        ];
    }

    /**
     * @return array{id: int, sku: string, linea: string, descripcion: string, formato: string, marca: string, sabor: string, um: string, pallets_por_hora: string, bph: string, compania: string, mercado: string, nivel: string, paq_cama: string, cartones: string, paq_pallet: string, activo: bool}
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
            'um' => (string) (int) $sku->um,
            'pallets_por_hora' => (string) (float) $sku->pallets_por_hora,
            'bph' => (string) (float) $sku->bph,
            'compania' => $sku->compania ?? '',
            'mercado' => $sku->mercado ?? '',
            'nivel' => (string) (int) $sku->nivel,
            'paq_cama' => (string) (int) $sku->paq_cama,
            'cartones' => (string) (int) $sku->cartones,
            'paq_pallet' => (string) (int) $sku->paq_pallet,
            'activo' => $sku->activo,
        ];
    }

    /**
     * @return array<int, array{id: int, linea: string, bphAnterior: float|null, bphNuevo: float, userName: string|null, createdAt: string}>
     */
    public function bphChanges(OeeSku $sku): array
    {
        return $sku->bphChanges()
            ->with('user:id,name')
            ->get()
            ->map(fn (OeeSkuBphChange $change): array => [
                'id' => $change->id,
                'linea' => $change->linea,
                'bphAnterior' => $change->bph_anterior === null ? null : (float) $change->bph_anterior,
                'bphNuevo' => (float) $change->bph_nuevo,
                'userName' => $change->user?->name,
                'createdAt' => $change->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
            ])
            ->all();
    }
}
