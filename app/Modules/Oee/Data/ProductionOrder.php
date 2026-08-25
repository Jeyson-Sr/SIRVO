<?php

namespace App\Modules\Oee\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A production order number, normalised to the plant's `YYYYNNNNNN` format.
 *
 * Operators type the order in several shapes ("424", "000424", "2026000424"), and
 * all of them have to collapse to one canonical value or the uniqueness of a
 * production run cannot be enforced.
 */
readonly class ProductionOrder
{
    /**
     * Number of digits in the sequential part of the order.
     */
    public const SEQUENCE_LENGTH = 6;

    private function __construct(public string $value)
    {
        //
    }

    /**
     * Normalise operator input into a canonical production order.
     *
     * @throws InvalidArgumentException when the input holds no digits.
     */
    public static function fromInput(string|int $input, string|CarbonImmutable|null $date = null): self
    {
        $digits = preg_replace('/\D/', '', (string) $input) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('La orden de producción debe contener al menos un dígito.');
        }

        $sequence = str_pad(
            substr($digits, -self::SEQUENCE_LENGTH),
            self::SEQUENCE_LENGTH,
            '0',
            STR_PAD_LEFT,
        );

        return new self(self::yearOf($date).$sequence);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Get the year the order belongs to, defaulting to the current one.
     */
    private static function yearOf(string|CarbonImmutable|null $date): string
    {
        if ($date === null) {
            return (string) CarbonImmutable::now()->year;
        }

        $date = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);

        return (string) $date->year;
    }
}
