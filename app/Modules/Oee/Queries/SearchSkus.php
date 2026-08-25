<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeSku;

/**
 * Searches the finished-goods catalog for the recording type-ahead.
 */
class SearchSkus
{
    /**
     * Number of catalog entries returned per search.
     */
    public const LIMIT = 15;

    /**
     * @return array<int, array{sku: string, descripcion: string, formato: string|null, marca: string|null, sabor: string|null, palletsPorHora: float, bph: float}>
     */
    public function handle(?string $search, ?string $linea = null): array
    {
        return OeeSku::query()
            ->active()
            ->forLine($linea)
            ->search($search)
            ->orderBy('sku')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (OeeSku $sku): array => [
                'sku' => $sku->sku,
                'descripcion' => $sku->descripcion,
                'formato' => $sku->formato,
                'marca' => $sku->marca,
                'sabor' => $sku->sabor,
                'palletsPorHora' => (float) $sku->pallets_por_hora,
                'bph' => (float) $sku->bph,
            ])
            ->all();
    }
}
