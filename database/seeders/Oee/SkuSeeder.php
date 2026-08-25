<?php

namespace Database\Seeders\Oee;

use App\Modules\Oee\Models\OeeSku;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the finished-goods catalog used to autofill a shift header.
 */
class SkuSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the SKU catalog.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $entry) {
            OeeSku::query()->updateOrCreate(
                ['sku' => $entry['sku']],
                [
                    'linea' => $entry['linea'],
                    'descripcion' => $entry['descripcion'],
                    'formato' => $entry['formato'],
                    'marca' => $entry['marca'],
                    'sabor' => $entry['sabor'],
                    'pallets_por_hora' => $entry['pallets_por_hora'],
                    'bph' => $entry['bph'],
                    'activo' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{sku: string, linea: string, descripcion: string, formato: string, marca: string, sabor: string, pallets_por_hora: float, bph: float}>
     */
    private function catalog(): array
    {
        /** @var array<int, array{sku: string, linea: string, descripcion: string, formato: string, marca: string, sabor: string, pallets_por_hora: float, bph: float}> $catalog */
        $catalog = require database_path('data/oee_skus.php');

        return $catalog;
    }
}
