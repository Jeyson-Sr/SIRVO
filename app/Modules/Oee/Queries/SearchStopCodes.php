<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;

/**
 * Searches the stop catalog for the recording type-ahead.
 */
class SearchStopCodes
{
    /**
     * Number of catalog entries returned per unscoped search.
     */
    public const LIMIT = 25;

    /**
     * Number of catalog entries returned when browsing one loss family.
     */
    public const FAMILY_LIMIT = 1000;

    /**
     * @return array<int, array{codigo: string, detalle: string, tipo: string, tipoLabel: string, categoria: string|null, causa: string|null, recursoAfectado: string|null, esTetraPak: bool}>
     */
    public function handle(?string $search, ?StopType $tipo = null): array
    {
        return CodStop::query()
            ->active()
            ->search($search)
            ->when($tipo, fn ($query) => $query->where('tipo_parada', $tipo->value))
            ->orderByRaw('CASE WHEN codigo = ? THEN 0 ELSE 1 END', [mb_strtoupper(trim((string) $search))])
            ->orderBy('codigo')
            ->limit($tipo instanceof StopType ? self::FAMILY_LIMIT : self::LIMIT)
            ->get()
            ->map(fn (CodStop $code): array => [
                'codigo' => $code->codigo,
                'detalle' => $code->detalle,
                'tipo' => $code->tipo_parada->value,
                'tipoLabel' => $code->tipo_parada->label(),
                'categoria' => $code->categoria,
                'causa' => $code->causa,
                'recursoAfectado' => $code->recurso_afectado,
                'esTetraPak' => $code->es_tetra_pak,
            ])
            ->all();
    }
}
