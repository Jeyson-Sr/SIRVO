<?php

namespace App\Modules\Oee\Enums;

/**
 * Attainment status of a single production hour.
 *
 * Always derived from the recorded output, never accepted from the client.
 */
enum HourStatus: string
{
    case Pending = 'pending';
    case OnTarget = 'on_target';
    case Warning = 'warning';
    case Critical = 'critical';

    /**
     * Attainment ratio below which an hour is considered critical.
     */
    public const WARNING_THRESHOLD = 0.8;

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Sin registrar',
            self::OnTarget => 'En meta',
            self::Warning => 'En riesgo',
            self::Critical => 'Crítico',
        };
    }

    /**
     * Derive the status from the hour's target and recorded output.
     *
     * A null output means the operator has not entered a figure yet, which is
     * different from an explicit zero.
     */
    public static function fromOutput(float $estimated, ?float $produced): self
    {
        if ($produced === null || $estimated <= 0) {
            return self::Pending;
        }

        $attainment = $produced / $estimated;

        return match (true) {
            $attainment >= 1.0 => self::OnTarget,
            $attainment >= self::WARNING_THRESHOLD => self::Warning,
            default => self::Critical,
        };
    }
}
