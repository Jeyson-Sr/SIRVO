<?php

namespace App\Modules\Oee\Models;

use App\Modules\Oee\Enums\HourStatus;
use Carbon\CarbonImmutable;
use Database\Factories\Oee\OeeHourDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One hour slot within a production run.
 *
 * @property int $id
 * @property int $oee_production_id
 * @property int $hour_index
 * @property string $hour_range
 * @property string $duration_minutes
 * @property string|null $sku
 * @property string|null $formato
 * @property string $pallets_por_hora
 * @property string $bph
 * @property string $estimado
 * @property string|null $producido
 * @property bool $closed
 * @property CarbonImmutable|null $closed_at
 * @property string|null $comment_mnf
 * @property string|null $comment_mantto
 * @property string|null $comment_calidad
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read OeeProduction $production
 * @property-read Collection<int, OeeStopDetail> $stops
 * @property-read HourStatus $status
 * @property-read float $minutos_a_justificar
 * @property-read float $minutos_justificados
 * @property-read float $minutos_pendientes
 */
#[Fillable([
    'oee_production_id',
    'hour_index',
    'hour_range',
    'duration_minutes',
    'sku',
    'formato',
    'pallets_por_hora',
    'bph',
    'estimado',
    'producido',
    'closed',
    'closed_at',
    'comment_mnf',
    'comment_mantto',
    'comment_calidad',
])]
class OeeHourDetail extends Model
{
    /** @use HasFactory<OeeHourDetailFactory> */
    use HasFactory;

    protected static function newFactory(): OeeHourDetailFactory
    {
        return OeeHourDetailFactory::new();
    }

    protected $attributes = [
        'duration_minutes' => 60,
        'pallets_por_hora' => 0,
        'bph' => 0,
    ];

    /**
     * Minutes contained in a single hour slot.
     */
    public const MINUTES_PER_HOUR = 60;

    /**
     * A shift of twelve clock hours plus a few product-change cuts.
     */
    public const MAX_SLICES_PER_SHIFT = 16;

    /**
     * Leftover minutes that still count as squared. Matches the one-decimal
     * figures the operator sees, so a displayed "0 min" unlocks the next hour.
     */
    public const BALANCE_TOLERANCE_MINUTES = 0.1;

    /**
     * Get the production run this hour belongs to.
     *
     * @return BelongsTo<OeeProduction, $this>
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(OeeProduction::class, 'oee_production_id');
    }

    /**
     * Get the stops recorded during this hour.
     *
     * @return HasMany<OeeStopDetail, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(OeeStopDetail::class, 'oee_hour_detail_id');
    }

    /**
     * Minutes of downtime implied by an hour falling short of its target.
     */
    public static function minutesToJustify(
        ?float $estimated,
        ?float $produced,
        float $durationMinutes = self::MINUTES_PER_HOUR,
    ): float {
        $estimatedOutput = $estimated ?? 0.0;
        $slotMinutes = max(0.0, $durationMinutes);

        if ($produced === null || $estimatedOutput <= 0.0 || $produced >= $estimatedOutput || $slotMinutes <= 0.0) {
            return 0.0;
        }

        return round((($estimatedOutput - $produced) / $estimatedOutput) * $slotMinutes, 2);
    }

    /**
     * Whether produced output is recorded and every shortfall minute is explained.
     */
    public static function isBalanced(
        ?float $estimated,
        ?float $produced,
        float $justifiedMinutes,
        float $durationMinutes = self::MINUTES_PER_HOUR,
    ): bool {
        if ($produced === null) {
            return false;
        }

        if ($justifiedMinutes > $durationMinutes) {
            return false;
        }

        return self::minutesToJustify($estimated, $produced, $durationMinutes) <= $justifiedMinutes + self::BALANCE_TOLERANCE_MINUTES;
    }

    /**
     * Determine whether the recorded stops cover the production shortfall.
     */
    public function isFullyJustified(): bool
    {
        return $this->minutos_pendientes <= 0.0;
    }

    /**
     * Determine whether the hour can unlock the next slot.
     */
    public function isBalancedHour(): bool
    {
        return self::isBalanced(
            (float) $this->estimado,
            $this->producido === null ? null : (float) $this->producido,
            $this->minutos_justificados,
            (float) $this->duration_minutes,
        );
    }

    /**
     * Scope the query to closed hours only.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('closed', true);
    }

    /**
     * Get the ratio of produced output against the target.
     *
     * Null when there is nothing to compare yet, which the caller must treat
     * differently from a genuine zero.
     */
    public function attainment(): ?float
    {
        $estimatedOutput = (float) $this->estimado;

        if ($this->producido === null || $estimatedOutput <= 0.0) {
            return null;
        }

        return (float) $this->producido / $estimatedOutput;
    }

    /**
     * Get the attainment status derived from the recorded output.
     *
     * @return Attribute<HourStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): HourStatus => HourStatus::fromOutput(
            (float) $this->estimado,
            $this->producido === null ? null : (float) $this->producido,
        ));
    }

    /**
     * Get the minutes of downtime implied by the production shortfall.
     *
     * @return Attribute<float, never>
     */
    protected function minutosAJustificar(): Attribute
    {
        return Attribute::get(fn (): float => self::minutesToJustify(
            (float) $this->estimado,
            $this->producido === null ? null : (float) $this->producido,
            (float) $this->duration_minutes,
        ));
    }

    /**
     * Get the minutes already accounted for by recorded stops.
     *
     * @return Attribute<float, never>
     */
    protected function minutosJustificados(): Attribute
    {
        return Attribute::get(fn (): float => round((float) $this->stops->sum(
            fn (OeeStopDetail $stop) => (float) $stop->tiempo_minutos,
        ), 2));
    }

    /**
     * Get the minutes of shortfall still missing an explanation.
     *
     * @return Attribute<float, never>
     */
    protected function minutosPendientes(): Attribute
    {
        return Attribute::get(fn (): float => round(
            max(0.0, $this->minutos_a_justificar - $this->minutos_justificados),
            2,
        ));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'decimal:2',
            'pallets_por_hora' => 'decimal:2',
            'bph' => 'decimal:2',
            'estimado' => 'decimal:2',
            'producido' => 'decimal:2',
            'closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }
}
