<?php

namespace App\Modules\Oee\Models;

use App\Modules\Oee\Enums\StopType;
use Carbon\CarbonImmutable;
use Database\Factories\Oee\OeeStopDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A downtime event recorded against a production hour.
 *
 * @property int $id
 * @property int $oee_hour_detail_id
 * @property int|null $cod_stop_id
 * @property string $client_uuid
 * @property string $codigo
 * @property StopType $tipo
 * @property string|null $descripcion
 * @property string|null $comentario
 * @property string $tiempo_minutos
 * @property int $frecuencia
 * @property bool $continua
 * @property CarbonImmutable $registered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read OeeHourDetail $hour
 * @property-read CodStop|null $codStop
 */
#[Fillable([
    'oee_hour_detail_id',
    'cod_stop_id',
    'client_uuid',
    'codigo',
    'tipo',
    'descripcion',
    'comentario',
    'tiempo_minutos',
    'frecuencia',
    'continua',
    'registered_at',
])]
class OeeStopDetail extends Model
{
    /** @use HasFactory<OeeStopDetailFactory> */
    use HasFactory;

    protected static function newFactory(): OeeStopDetailFactory
    {
        return OeeStopDetailFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'continua' => false,
    ];

    /**
     * Get the hour the stop was recorded against.
     *
     * @return BelongsTo<OeeHourDetail, $this>
     */
    public function hour(): BelongsTo
    {
        return $this->belongsTo(OeeHourDetail::class, 'oee_hour_detail_id');
    }

    /**
     * Get the catalog entry the stop was classified with.
     *
     * @return BelongsTo<CodStop, $this>
     */
    public function codStop(): BelongsTo
    {
        return $this->belongsTo(CodStop::class, 'cod_stop_id');
    }

    /**
     * Get the total downtime contributed by the stop.
     */
    public function totalMinutes(): float
    {
        return (float) $this->tiempo_minutos;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => StopType::class,
            'tiempo_minutos' => 'decimal:2',
            'frecuencia' => 'integer',
            'continua' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }
}
