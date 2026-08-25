<?php

namespace App\Modules\Oee\Http\Requests;

use App\Modules\Oee\Data\ProductionFilters;
use App\Modules\Oee\Enums\StopRanking;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Queries\RankStops;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionFilterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'day' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'integer', 'min:1', 'max:53'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'linea' => ['nullable', 'string', 'max:32'],
            'marca' => ['nullable', 'string', 'max:64'],
            'componente' => ['nullable', Rule::enum(StopType::class)],
            'closed_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', Rule::enum(StopRanking::class)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.RankStops::MAX_LIMIT],
        ];
    }

    /**
     * Get the reporting filters described by the request.
     */
    public function filters(): ProductionFilters
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ProductionFilters::fromArray([
            ...$validated,
            'closed_only' => $this->boolean('closed_only', true),
        ]);
    }

    /**
     * Get the measure the stop ranking should be ordered by.
     */
    public function ranking(): StopRanking
    {
        return StopRanking::tryFrom((string) $this->validated('sort_by')) ?? StopRanking::Minutes;
    }

    /**
     * Get the number of stop codes the ranking should return.
     */
    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? RankStops::DEFAULT_LIMIT);
    }

    /**
     * @return array{from: string|null, to: string|null, linea: string|null, marca: string|null, componente: string|null, closedOnly: bool, sortBy: string, limit: int}
     */
    public function applied(): array
    {
        $filters = $this->filters();

        return [
            'from' => $filters->from?->toDateString(),
            'to' => $filters->to?->toDateString(),
            'linea' => $filters->linea,
            'marca' => $filters->marca,
            'componente' => $filters->componente?->value,
            'closedOnly' => $filters->closedOnly,
            'sortBy' => $this->ranking()->value,
            'limit' => $this->limit(),
        ];
    }
}
