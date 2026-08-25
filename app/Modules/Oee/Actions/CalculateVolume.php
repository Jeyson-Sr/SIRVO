<?php

namespace App\Modules\Oee\Actions;

/**
 * Converts pallets produced in an hour into consumer units (CU).
 *
 * unidades = PH_producidas × (BPH / PH_meta)
 * CU       = unidades × litraje / 30
 *
 * BPH / PH_meta is the bottles on one pallet, so the pack size cancels out:
 * packs_per_pallet × units_per_pack = bottles per pallet.
 */
class CalculateVolume
{
    /**
     * Liters that make one consumer unit.
     */
    public const LITERS_PER_CU = 30.0;

    /**
     * Derive the CU for one hour of recorded pallets.
     */
    public function handle(float $palletsProduced, float $palletsPerHour, float $bph, float $liters): float
    {
        if ($palletsProduced <= 0.0 || $palletsPerHour <= 0.0 || $bph <= 0.0 || $liters <= 0.0) {
            return 0.0;
        }

        $bottles = $palletsProduced * ($bph / $palletsPerHour);

        return round($bottles * $liters / self::LITERS_PER_CU, 2);
    }
}
