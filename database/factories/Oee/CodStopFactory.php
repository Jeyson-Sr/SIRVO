<?php

namespace Database\Factories\Oee;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodStop>
 */
class CodStopFactory extends Factory
{
    protected $model = CodStop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(StopType::cases());

        return [
            'codigo' => mb_strtoupper(fake()->unique()->bothify('??##')),
            'detalle' => fake()->sentence(3),
            'tipo_parada' => $type,
            'categoria' => $type->label(),
            'causa' => fake()->optional()->sentence(2),
            'recurso_afectado' => fake()->randomElement(['Llenadora', 'Etiquetadora', 'Sopladora', 'Paletizadora']),
            'familia_oee' => $type->value,
            'es_tetra_pak' => false,
            'activo' => true,
        ];
    }

    /**
     * Indicate the loss family the code belongs to.
     */
    public function ofType(StopType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_parada' => $type,
            'categoria' => $type->label(),
            'familia_oee' => $type->value,
        ]);
    }

    /**
     * Indicate that the code is retired from use.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indicate that the code applies only to Tetra Pak lines.
     */
    public function tetraPak(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_tetra_pak' => true,
        ]);
    }
}
