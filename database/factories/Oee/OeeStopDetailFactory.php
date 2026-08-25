<?php

namespace Database\Factories\Oee;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeStopDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OeeStopDetail>
 */
class OeeStopDetailFactory extends Factory
{
    protected $model = OeeStopDetail::class;

    /**
     * Define the model's default state.
     *
     * A catalog entry is created up front so the code, loss family and foreign key
     * always describe the same stop. Use `forCode()` or `ofType()` to control it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'oee_hour_detail_id' => OeeHourDetail::factory(),
            'client_uuid' => fake()->uuid(),
            'descripcion' => fake()->sentence(3),
            'comentario' => null,
            'tiempo_minutos' => fake()->randomFloat(2, 1, 20),
            'frecuencia' => fake()->numberBetween(1, 3),
            'continua' => false,
            'registered_at' => now(),
            ...$this->attributesFor(CodStop::factory()->create()),
        ];
    }

    /**
     * Charge the stop to an existing catalog entry.
     */
    public function forCode(CodStop $catalogEntry): static
    {
        return $this->state(fn (array $attributes) => $this->attributesFor($catalogEntry));
    }

    /**
     * Charge the stop to a loss family, creating a catalog entry for it.
     */
    public function ofType(StopType $stopType, ?float $minutes = null): static
    {
        return $this->state(function (array $attributes) use ($stopType, $minutes) {
            $catalogEntry = CodStop::factory()->ofType($stopType)->create();

            $state = $this->attributesFor($catalogEntry);

            if ($minutes !== null) {
                $state['tiempo_minutos'] = $minutes;
            }

            return $state;
        });
    }

    /**
     * Mark the stop as the same event continuing from the previous hour.
     */
    public function continuation(): static
    {
        return $this->state(fn (array $attributes) => [
            'continua' => true,
            'frecuencia' => 0,
        ]);
    }

    /**
     * Set the downtime the stop accounts for.
     */
    public function minutes(float $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'tiempo_minutos' => $minutes,
        ]);
    }

    /**
     * Derive the columns a stop copies from its catalog entry.
     *
     * @return array<string, mixed>
     */
    private function attributesFor(CodStop $catalogEntry): array
    {
        return [
            'cod_stop_id' => $catalogEntry->id,
            'codigo' => $catalogEntry->codigo,
            'tipo' => $catalogEntry->tipo_parada,
        ];
    }
}
