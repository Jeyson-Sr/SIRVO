<?php

namespace Database\Factories\Oee;

use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Support\SkuRates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OeeSku>
 */
class OeeSkuFactory extends Factory
{
    protected $model = OeeSku::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array<int, string> $lines */
        $lines = require database_path('data/oee_lines.php');

        $um = fake()->randomElement([6, 12, 15]);
        $nivel = fake()->numberBetween(6, 10);
        $paqCama = fake()->randomElement([16, 20, 25]);
        $bph = fake()->randomFloat(2, 20000, 60000);
        $rates = SkuRates::fromInput([
            'bph' => $bph,
            'um' => $um,
            'paq_cama' => $paqCama,
            'nivel' => $nivel,
        ]);

        return [
            'sku' => (string) fake()->unique()->numberBetween(400000, 499999),
            'linea' => fake()->randomElement($lines),
            'descripcion' => fake()->words(5, true),
            'formato' => fake()->randomElement(['0.300', '0.625', '1']),
            'marca' => fake()->randomElement(['CIELO', 'VOLT', 'CIFRUT']),
            'sabor' => fake()->randomElement(['AGUA', 'NARANJA', 'PUNCH']),
            'um' => $um,
            'pallets_por_hora' => $rates['pallets_por_hora'],
            'bph' => $bph,
            'compania' => 'AJE CARAL',
            'mercado' => fake()->randomElement(['PERU', 'CHILE', 'USA']),
            'nivel' => $nivel,
            'paq_cama' => $paqCama,
            'cartones' => $nivel,
            'paq_pallet' => $rates['paq_pallet'],
            'activo' => true,
        ];
    }

    /**
     * Indicate that the SKU is retired from the catalog.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
