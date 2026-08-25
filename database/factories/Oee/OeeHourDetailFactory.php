<?php

namespace Database\Factories\Oee;

use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OeeHourDetail>
 */
class OeeHourDetailFactory extends Factory
{
    protected $model = OeeHourDetail::class;

    /**
     * Define the model's default state.
     *
     * The hour index defaults to the first slot because it is unique per run;
     * callers creating several hours set it explicitly or via `atHour()`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'oee_production_id' => OeeProduction::factory(),
            'hour_index' => 0,
            'hour_range' => '06:30 - 07:30',
            'duration_minutes' => 60,
            'estimado' => fake()->randomFloat(2, 8, 30),
            'producido' => fake()->randomFloat(2, 4, 30),
            'closed' => true,
            'closed_at' => now(),
            'comment_mnf' => null,
            'comment_mantto' => null,
            'comment_calidad' => null,
        ];
    }

    /**
     * Place the record in a specific hour slot of the shift.
     */
    public function atHour(int $index): static
    {
        return $this->state(fn (array $attributes) => [
            'hour_index' => $index,
        ]);
    }

    /**
     * Set the target and actual output of the hour.
     */
    public function output(float $estimated, ?float $produced): static
    {
        return $this->state(fn (array $attributes) => [
            'estimado' => $estimated,
            'producido' => $produced,
        ]);
    }

    /**
     * Indicate that the hour is still open for edits.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed' => false,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that no output has been recorded yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'producido' => null,
            'closed' => false,
            'closed_at' => null,
        ]);
    }
}
