<?php

namespace App\Modules\Oee\Http\Requests;

use App\Modules\Oee\Actions\FillRecordingDefaults;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Models\OeeHourDetail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProductionRequest extends FormRequest
{
    /**
     * Largest number of stops accepted for a single hour.
     */
    private const MAX_STOPS_PER_HOUR = 60;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'production' => ['required', 'array'],
            'production.fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'production.turno' => ['required', Rule::enum(Shift::class)],
            'production.linea' => ['required', 'string', 'max:32'],
            'production.op' => ['required', 'string', 'max:16', 'regex:/\d/'],
            'production.ingeniero' => ['required', 'string', 'max:255'],
            'production.operador' => ['required', 'string', 'max:255'],
            'production.sku' => ['required', 'string', 'max:32'],
            'production.descripcion' => ['nullable', 'string', 'max:255'],
            'production.formato' => ['nullable', 'string', 'max:32'],
            'production.marca' => ['nullable', 'string', 'max:64'],
            'production.sabor' => ['nullable', 'string', 'max:64'],
            'production.pallets_por_hora' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'production.bph' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],

            'hours' => ['required', 'array', 'max:'.OeeHourDetail::MAX_SLICES_PER_SHIFT],
            'hours.*.hour_index' => ['required', 'integer', 'min:0', 'max:'.(OeeHourDetail::MAX_SLICES_PER_SHIFT - 1), 'distinct'],
            'hours.*.hour_range' => ['required', 'string', 'max:16'],
            'hours.*.duration_minutes' => ['nullable', 'numeric', 'min:0.01', 'max:'.OeeHourDetail::MINUTES_PER_HOUR],
            'hours.*.sku' => ['nullable', 'string', 'max:32'],
            'hours.*.formato' => ['nullable', 'string', 'max:32'],
            'hours.*.pallets_por_hora' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'hours.*.bph' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'hours.*.estimado' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'hours.*.producido' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'hours.*.closed' => ['required', 'boolean'],
            'hours.*.comments' => ['nullable', 'array'],
            'hours.*.comments.mnf' => ['nullable', 'string', 'max:1000'],
            'hours.*.comments.mantto' => ['nullable', 'string', 'max:1000'],
            'hours.*.comments.calidad' => ['nullable', 'string', 'max:1000'],

            'hours.*.stops' => ['nullable', 'array', 'max:'.self::MAX_STOPS_PER_HOUR],
            'hours.*.stops.*.client_uuid' => ['required', 'uuid', 'distinct'],
            'hours.*.stops.*.codigo' => [
                'required',
                'string',
                Rule::exists('cod_stops', 'codigo')->where('activo', true),
            ],
            'hours.*.stops.*.descripcion' => ['nullable', 'string', 'max:255'],
            'hours.*.stops.*.comentario' => ['nullable', 'string', 'max:1000'],
            'hours.*.stops.*.tiempo_minutos' => ['required', 'numeric', 'min:0.01', 'max:'.OeeHourDetail::MINUTES_PER_HOUR],
            'hours.*.stops.*.frecuencia' => ['nullable', 'integer', 'min:0', 'max:999'],
            'hours.*.stops.*.continua' => ['nullable', 'boolean'],
            'hours.*.stops.*.registered_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Normalise the payload before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $production = $this->input('production');
        $hours = $this->input('hours');

        if (is_array($production)) {
            if (isset($production['sku'])) {
                $production['sku'] = trim((string) $production['sku']);
            }

            if (blank($production['ingeniero'] ?? null)) {
                $production['ingeniero'] = $this->user()?->name;
            }
        }

        if (is_array($hours)) {
            $hours = array_map(function (mixed $hour): mixed {
                if (! is_array($hour) || ! is_array($hour['stops'] ?? null)) {
                    return $hour;
                }

                $hour['stops'] = array_map(function (mixed $stop): mixed {
                    if (is_array($stop) && isset($stop['codigo'])) {
                        $stop['codigo'] = mb_strtoupper(trim((string) $stop['codigo']));
                    }

                    return $stop;
                }, $hour['stops']);

                return $hour;
            }, $hours);
        }

        $filled = $this->container->make(FillRecordingDefaults::class)->handle([
            'production' => is_array($production) ? $production : [],
            'hours' => is_array($hours) ? $hours : [],
        ]);

        $this->merge($filled);
    }

    /**
     * Add the checks that span more than one field.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('hours', []) as $index => $hour) {
                    $this->validateProducedDoesNotExceedTarget($validator, (int) $index, $hour);
                    $this->validateDowntimeFitsInHour($validator, (int) $index, $hour);
                    $this->validateHourIsReadyToClose($validator, (int) $index, $hour);
                }

                $this->validateHoursAdvanceInOrder($validator);
            },
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'production.fecha' => 'fecha',
            'production.turno' => 'turno',
            'production.linea' => 'línea',
            'production.op' => 'orden de producción',
            'production.sku' => 'SKU',
            'production.ingeniero' => 'ingeniero',
            'production.operador' => 'operador',
            'production.pallets_por_hora' => 'pallets por hora',
            'hours' => 'horas',
            'hours.*.hour_index' => 'índice de hora',
            'hours.*.hour_range' => 'rango horario',
            'hours.*.duration_minutes' => 'minutos de la hora',
            'hours.*.estimado' => 'estimado',
            'hours.*.producido' => 'producido',
            'hours.*.stops' => 'paradas',
            'hours.*.stops.*.codigo' => 'código de parada',
            'hours.*.stops.*.tiempo_minutos' => 'minutos de parada',
            'hours.*.stops.*.frecuencia' => 'frecuencia',
        ];
    }

    /**
     * Produced pallets cannot beat the hour's target. Over the meta is not output.
     *
     * @param  mixed  $hour
     */
    private function validateProducedDoesNotExceedTarget(Validator $validator, int $index, $hour): void
    {
        if (! is_array($hour) || blank($hour['producido'] ?? null)) {
            return;
        }

        $produced = (float) $hour['producido'];
        $estimated = blank($hour['estimado'] ?? null) ? 0.0 : (float) $hour['estimado'];

        if ($produced > $estimated) {
            $validator->errors()->add(
                "hours.{$index}.producido",
                'Lo producido no puede superar la meta de la hora.',
            );
        }
    }

    /**
     * An hour cannot hold more downtime than the sixty minutes it lasts.
     *
     * @param  mixed  $hour
     */
    private function validateDowntimeFitsInHour(Validator $validator, int $index, $hour): void
    {
        if (! is_array($hour) || ! is_array($hour['stops'] ?? null)) {
            return;
        }

        $total = array_sum(array_map(
            fn (mixed $stop) => is_array($stop) ? (float) ($stop['tiempo_minutos'] ?? 0) : 0.0,
            $hour['stops'],
        ));

        $limit = $this->durationOf($hour);

        if ($total > $limit) {
            $validator->errors()->add(
                "hours.{$index}.stops",
                "Las paradas de la hora suman {$total} minutos y no pueden exceder {$limit}.",
            );
        }
    }

    /**
     * A closed hour must have produced output and every shortfall minute explained.
     *
     * @param  mixed  $hour
     */
    private function validateHourIsReadyToClose(Validator $validator, int $index, $hour): void
    {
        if (! is_array($hour) || ! ($hour['closed'] ?? false)) {
            return;
        }

        if ($this->hourIsBalanced($hour)) {
            return;
        }

        $validator->errors()->add(
            "hours.{$index}.stops",
            'No se puede cerrar la hora: cuadra los minutos de parada con lo que faltó de producir.',
        );
    }

    /**
     * Later hours cannot be recorded until every earlier slot is balanced.
     */
    private function validateHoursAdvanceInOrder(Validator $validator): void
    {
        $hours = $this->input('hours', []);

        if (! is_array($hours)) {
            return;
        }

        $bySlot = [];

        foreach ($hours as $payloadIndex => $hour) {
            if (is_array($hour) && isset($hour['hour_index'])) {
                $bySlot[(int) $hour['hour_index']] = ['key' => (int) $payloadIndex, 'hour' => $hour];
            }
        }

        ksort($bySlot);

        foreach ($bySlot as $hourIndex => $entry) {
            if ($hourIndex === 0 || ! $this->hourIsStarted($entry['hour'])) {
                continue;
            }

            for ($previous = 0; $previous < $hourIndex; $previous++) {
                $previousHour = $bySlot[$previous]['hour'] ?? null;

                if (is_array($previousHour) && $this->hourIsBalanced($previousHour)) {
                    continue;
                }

                $validator->errors()->add(
                    "hours.{$entry['key']}.producido",
                    'Por favor cuadra los tiempos de la hora anterior.',
                );

                break;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $hour
     */
    private function hourIsStarted(array $hour): bool
    {
        if (! blank($hour['producido'] ?? null)) {
            return true;
        }

        return is_array($hour['stops'] ?? null) && $hour['stops'] !== [];
    }

    /**
     * @param  array<string, mixed>  $hour
     */
    private function hourIsBalanced(array $hour): bool
    {
        $produced = blank($hour['producido'] ?? null) ? null : (float) $hour['producido'];
        $justified = 0.0;

        foreach ($hour['stops'] ?? [] as $stop) {
            if (is_array($stop)) {
                $justified += (float) ($stop['tiempo_minutos'] ?? 0);
            }
        }

        return OeeHourDetail::isBalanced(
            blank($hour['estimado'] ?? null) ? 0.0 : (float) $hour['estimado'],
            $produced,
            $justified,
            $this->durationOf($hour),
        );
    }

    /**
     * @param  array<string, mixed>  $hour
     */
    private function durationOf(array $hour): float
    {
        if (blank($hour['duration_minutes'] ?? null)) {
            return (float) OeeHourDetail::MINUTES_PER_HOUR;
        }

        return (float) $hour['duration_minutes'];
    }
}
