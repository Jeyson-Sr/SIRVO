<?php

namespace Database\Factories\Oee;

use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeSkuBphChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OeeSkuBphChange>
 */
class OeeSkuBphChangeFactory extends Factory
{
    protected $model = OeeSkuBphChange::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'oee_sku_id' => OeeSku::factory(),
            'sku' => fn (array $attributes): string => OeeSku::query()->find($attributes['oee_sku_id'])?->sku ?? '408462',
            'linea' => 'LINEA 1',
            'bph_anterior' => 50000,
            'bph_nuevo' => 60000,
        ];
    }
}
