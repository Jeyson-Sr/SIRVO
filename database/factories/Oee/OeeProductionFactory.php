<?php

namespace Database\Factories\Oee;

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Models\OeeProduction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OeeProduction>
 */
class OeeProductionFactory extends Factory
{
    protected $model = OeeProduction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = CarbonImmutable::parse(fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'));

        return [
            'team_id' => Team::factory(),
            'created_by' => User::factory(),
            'fecha' => $date->toDateString(),
            'turno' => fake()->randomElement(Shift::cases()),
            'linea' => 'L'.fake()->numberBetween(1, 14),
            'op' => $date->year.fake()->unique()->numerify('######'),
            'ingeniero' => fake()->name(),
            'operador' => fake()->name(),
            'sku' => (string) fake()->numberBetween(10000, 99999),
            'descripcion' => fake()->words(3, true),
            'formato' => fake()->randomElement(['0.300', '0.400', '0.625']),
            'marca' => fake()->randomElement(['KR', 'BIG', 'VOLT', 'CIELO']),
            'sabor' => fake()->randomElement(['Naranja', 'Piña', 'Cola', 'Sin sabor']),
            'pallets_por_hora' => fake()->randomFloat(2, 8, 30),
            'bph' => fake()->randomFloat(2, 10000, 40000),
            'closed_at' => null,
        ];
    }

    /**
     * Use a sheet where one pallet is one CU, so report tests can assert produced totals.
     */
    public function knownSheet(): static
    {
        return $this->state(fn (array $attributes) => [
            'formato' => '1',
            'pallets_por_hora' => 10,
            'bph' => 300,
        ]);
    }

    /**
     * Place the run on a specific date.
     */
    public function on(string|CarbonImmutable $date): static
    {
        $date = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);

        return $this->state(fn (array $attributes) => [
            'fecha' => $date->toDateString(),
            'op' => $date->year.fake()->unique()->numerify('######'),
        ]);
    }

    /**
     * Place the run on a specific production line.
     */
    public function onLine(string $line): static
    {
        return $this->state(fn (array $attributes) => [
            'linea' => $line,
        ]);
    }

    /**
     * Indicate that the shift has been signed off.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
        ]);
    }
}
