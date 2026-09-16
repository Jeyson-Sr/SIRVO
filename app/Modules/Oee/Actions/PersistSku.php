<?php

namespace App\Modules\Oee\Actions;

use App\Models\User;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeSkuBphChange;
use App\Modules\Oee\Support\SkuRates;
use Illuminate\Support\Facades\DB;

class PersistSku
{
    /**
     * Create or update a catalog row and record BPH changes per line.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?OeeSku $sku = null, ?User $actor = null): OeeSku
    {
        $data = [...$data, ...SkuRates::fromInput($data)];

        return DB::transaction(function () use ($data, $sku, $actor): OeeSku {
            $previousBph = $sku === null ? null : (float) $sku->bph;
            $previousLinea = $sku?->linea;

            if ($sku === null) {
                $sku = OeeSku::query()->create($data);
            } else {
                $sku->update($data);
                $sku->refresh();
            }

            $nextBph = (float) $sku->bph;
            $bphChanged = $previousBph === null || abs($previousBph - $nextBph) > 0.009;
            $lineChanged = $previousLinea !== null && $previousLinea !== $sku->linea;

            if ($bphChanged || $lineChanged) {
                OeeSkuBphChange::query()->create([
                    'oee_sku_id' => $sku->id,
                    'sku' => $sku->sku,
                    'linea' => $sku->linea,
                    'bph_anterior' => $previousBph,
                    'bph_nuevo' => $nextBph,
                    'user_id' => $actor?->id,
                ]);
            }

            return $sku;
        });
    }
}
