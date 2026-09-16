<?php

namespace Database\Seeders\Oee;

use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Support\SkuRates;
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
            $rates = SkuRates::fromInput($entry);

            OeeSku::query()->updateOrCreate(
                [
                    'sku' => $entry['sku'],
                    'linea' => $entry['linea'],
                ],
                [
                    'descripcion' => $entry['descripcion'],
                    'formato' => $entry['formato'],
                    'marca' => $entry['marca'],
                    'sabor' => $entry['sabor'],
                    'um' => $entry['um'],
                    'pallets_por_hora' => $rates['pallets_por_hora'],
                    'bph' => $entry['bph'],
                    'compania' => $entry['compania'],
                    'mercado' => $entry['mercado'],
                    'nivel' => $entry['nivel'],
                    'paq_cama' => $entry['paq_cama'],
                    'cartones' => $entry['cartones'],
                    'paq_pallet' => $rates['paq_pallet'],
                    'activo' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{
     *     sku: string,
     *     linea: string,
     *     descripcion: string,
     *     formato: string,
     *     marca: string,
     *     sabor: string,
     *     um: int,
     *     pallets_por_hora: float,
     *     bph: float,
     *     compania: string,
     *     mercado: string,
     *     nivel: int,
     *     paq_cama: int,
     *     cartones: int,
     *     paq_pallet: int
     * }>
     */
    private function catalog(): array
    {
        /** @var array<int, array{
         *     sku: string,
         *     linea: string,
         *     descripcion: string,
         *     formato: string,
         *     marca: string,
         *     sabor: string,
         *     um: int,
         *     pallets_por_hora: float,
         *     bph: float,
         *     compania: string,
         *     mercado: string,
         *     nivel: int,
         *     paq_cama: int,
         *     cartones: int,
         *     paq_pallet: int
         * }> $catalog */
        $catalog = require database_path('data/oee_skus.php');

        return $catalog;
    }
}
