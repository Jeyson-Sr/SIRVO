<?php

namespace App\Modules\Oee\Data;

use App\Modules\Oee\Enums\StopType;
use Carbon\CarbonImmutable;

/**
 * The narrowing applied to a set of production runs before reporting on them.
 *
 * Calendar selections are resolved to an explicit date range here rather than in
 * SQL, which keeps the queries portable across database drivers and lets them
 * use the index on (team_id, fecha).
 */
readonly class ProductionFilters
{
    public function __construct(
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?string $linea = null,
        public ?string $marca = null,
        public ?StopType $componente = null,
        public bool $closedOnly = true,
    ) {
        //
    }

    /**
     * Build the filters from validated request input.
     *
     * @param  array{
     *     from?: string|null,
     *     to?: string|null,
     *     day?: string|null,
     *     week?: int|string|null,
     *     month?: int|string|null,
     *     year?: int|string|null,
     *     linea?: string|null,
     *     marca?: string|null,
     *     componente?: string|null,
     *     closed_only?: bool|null,
     * }  $input
     */
    public static function fromArray(array $input): self
    {
        [$from, $to] = self::resolveRange($input);

        return new self(
            from: $from,
            to: $to,
            linea: self::trimmed($input['linea'] ?? null),
            marca: self::trimmed($input['marca'] ?? null),
            componente: StopType::fromLabel($input['componente'] ?? null),
            closedOnly: (bool) ($input['closed_only'] ?? true),
        );
    }

    /**
     * Determine whether any date narrowing is applied.
     */
    public function hasDateRange(): bool
    {
        return $this->from !== null || $this->to !== null;
    }

    /**
     * Resolve the calendar selection into a concrete date range.
     *
     * The most specific selection wins: an explicit range, then a day, then an
     * ISO week, then a month, then a whole year.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private static function resolveRange(array $input): array
    {
        $from = self::date($input['from'] ?? null);
        $to = self::date($input['to'] ?? null);

        if ($from !== null || $to !== null) {
            return [$from, $to];
        }

        if ($day = self::date($input['day'] ?? null)) {
            return [$day, $day];
        }

        $year = self::integer($input['year'] ?? null) ?? CarbonImmutable::now()->year;

        if ($week = self::integer($input['week'] ?? null)) {
            $start = CarbonImmutable::now()->setISODate($year, $week, 1)->startOfDay();

            return [$start, $start->addDays(6)];
        }

        if ($month = self::integer($input['month'] ?? null)) {
            $start = CarbonImmutable::create($year, $month, 1);

            return [$start, $start->endOfMonth()->startOfDay()];
        }

        if (self::integer($input['year'] ?? null) !== null) {
            $start = CarbonImmutable::create($year, 1, 1);

            return [$start, $start->endOfYear()->startOfDay()];
        }

        return [null, null];
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->startOfDay();
    }

    private static function integer(mixed $value): ?int
    {
        return blank($value) ? null : (int) $value;
    }

    private static function trimmed(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
