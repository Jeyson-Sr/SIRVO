<?php

namespace App\Modules\Oee\Models;

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Policies\OeeProductionPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\Oee\OeeProductionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single production run: one line, one shift, one production order.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $created_by
 * @property CarbonImmutable $fecha
 * @property Shift $turno
 * @property string $linea
 * @property string $op
 * @property string|null $ingeniero
 * @property string|null $operador
 * @property string|null $sku
 * @property string|null $descripcion
 * @property string|null $formato
 * @property string|null $marca
 * @property string|null $sabor
 * @property string $pallets_por_hora
 * @property string $bph
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $team
 * @property-read User|null $creator
 * @property-read Collection<int, OeeHourDetail> $hours
 * @property-read int|null $hours_count
 * @property-read int|null $closed_hours_count Populated by the listing query's withCount alias.
 */
#[Fillable([
    'team_id',
    'created_by',
    'fecha',
    'turno',
    'linea',
    'op',
    'ingeniero',
    'operador',
    'sku',
    'descripcion',
    'formato',
    'marca',
    'sabor',
    'pallets_por_hora',
    'bph',
    'closed_at',
])]
#[UsePolicy(OeeProductionPolicy::class)]
class OeeProduction extends Model
{
    /** @use HasFactory<OeeProductionFactory> */
    use HasFactory;

    protected static function newFactory(): OeeProductionFactory
    {
        return OeeProductionFactory::new();
    }

    /**
     * Get the team that owns the production run.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that opened the production run.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the hour slots of the production run.
     *
     * @return HasMany<OeeHourDetail, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(OeeHourDetail::class)->orderBy('hour_index');
    }

    /**
     * Determine whether the shift has been signed off.
     */
    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * The calendar day the run belongs to.
     *
     * A plain `date` cast writes `Y-m-d 00:00:00`, which then fails to match the
     * `Y-m-d` value used to look the run up by its natural key or to bound a
     * report. Storing the bare day keeps both exact on every driver.
     *
     * @return Attribute<CarbonImmutable, string>
     */
    protected function fecha(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): CarbonImmutable => CarbonImmutable::parse($value),
            set: fn (DateTimeInterface|string $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /**
     * Scope the query to a single team.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function forTeam(Builder $query, Team $team): void
    {
        $query->where('team_id', $team->id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'turno' => Shift::class,
            'pallets_por_hora' => 'decimal:2',
            'bph' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }
}
