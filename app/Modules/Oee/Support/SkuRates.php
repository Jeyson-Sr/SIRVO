<?php

namespace App\Modules\Oee\Support;

class SkuRates
{
    /**
     * Packs that fit on one pallet.
     */
    public static function paqPallet(int|float|string|null $paqCama, int|float|string|null $nivel): int
    {
        $packs = max(0, (int) $paqCama);
        $levels = max(0, (int) $nivel);

        return $packs * $levels;
    }

    /**
     * Pallet target per hour: (BPH / U.M) / packs per pallet.
     */
    public static function palletsPerHour(
        int|float|string|null $bph,
        int|float|string|null $um,
        int|float|string|null $paqPallet,
    ): float {
        $bottles = (float) $bph;
        $units = (float) $um;
        $packs = (float) $paqPallet;

        if ($bottles <= 0.0 || $units <= 0.0 || $packs <= 0.0) {
            return 0.0;
        }

        return round(($bottles / $units) / $packs, 2);
    }

    /**
     * @param  array{bph?: mixed, um?: mixed, paq_cama?: mixed, nivel?: mixed}  $input
     * @return array{paq_pallet: int, pallets_por_hora: float}
     */
    public static function fromInput(array $input): array
    {
        $paqPallet = self::paqPallet($input['paq_cama'] ?? 0, $input['nivel'] ?? 0);

        return [
            'paq_pallet' => $paqPallet,
            'pallets_por_hora' => self::palletsPerHour($input['bph'] ?? 0, $input['um'] ?? 0, $paqPallet),
        ];
    }
}
