<?php

namespace App\Modules\Oee\Models;

use App\Models\User;
use Database\Factories\Oee\OeeSkuBphChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recorded BPH change for a finished good on a line.
 *
 * @property int $id
 * @property int $oee_sku_id
 * @property string $sku
 * @property string $linea
 * @property string|null $bph_anterior
 * @property string $bph_nuevo
 * @property int|null $user_id
 * @property Carbon $created_at
 */
#[Fillable([
    'oee_sku_id',
    'sku',
    'linea',
    'bph_anterior',
    'bph_nuevo',
    'user_id',
])]
class OeeSkuBphChange extends Model
{
    /** @use HasFactory<OeeSkuBphChangeFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function newFactory(): OeeSkuBphChangeFactory
    {
        return OeeSkuBphChangeFactory::new();
    }

    /**
     * @return BelongsTo<OeeSku, $this>
     */
    public function skuEntry(): BelongsTo
    {
        return $this->belongsTo(OeeSku::class, 'oee_sku_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bph_anterior' => 'decimal:2',
            'bph_nuevo' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }
}
