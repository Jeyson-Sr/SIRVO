<?php

namespace App\Modules\Oee\Models;

use App\Modules\Oee\Policies\OeeSkuPolicy;
use Database\Factories\Oee\OeeSkuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A finished good the plant can run, keyed by its SKU and the line that makes it.
 *
 * @property int $id
 * @property string $sku
 * @property string $linea
 * @property string $descripcion
 * @property string|null $formato
 * @property string|null $marca
 * @property string|null $sabor
 * @property int $um
 * @property string $pallets_por_hora
 * @property string $bph
 * @property string|null $compania
 * @property string|null $mercado
 * @property int $nivel
 * @property int $paq_cama
 * @property int $cartones
 * @property int $paq_pallet
 * @property bool $activo
 */
#[UsePolicy(OeeSkuPolicy::class)]
#[Fillable([
    'sku',
    'linea',
    'descripcion',
    'formato',
    'marca',
    'sabor',
    'um',
    'pallets_por_hora',
    'bph',
    'compania',
    'mercado',
    'nivel',
    'paq_cama',
    'cartones',
    'paq_pallet',
    'activo',
])]
class OeeSku extends Model
{
    /** @use HasFactory<OeeSkuFactory> */
    use HasFactory;

    protected static function newFactory(): OeeSkuFactory
    {
        return OeeSkuFactory::new();
    }

    /**
     * Scope the query to active catalog entries.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * Scope the query to finished goods that run on the given line.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function forLine(Builder $query, ?string $linea): void
    {
        $linea = trim((string) $linea);

        if ($linea === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('linea', $linea);
    }

    /**
     * Scope the query to entries matching a free-text search term.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('sku', 'like', "%{$term}%")
                ->orWhere('descripcion', 'like', "%{$term}%")
                ->orWhere('marca', 'like', "%{$term}%");
        });
    }

    /**
     * @return HasMany<OeeSkuBphChange, $this>
     */
    public function bphChanges(): HasMany
    {
        return $this->hasMany(OeeSkuBphChange::class, 'oee_sku_id')->latest('id');
    }

    /**
     * Determine whether any recorded shift already used this SKU.
     */
    public function isInUse(): bool
    {
        return OeeProduction::query()->where('sku', $this->sku)->exists()
            || OeeHourDetail::query()->where('sku', $this->sku)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'um' => 'integer',
            'pallets_por_hora' => 'decimal:2',
            'bph' => 'decimal:2',
            'nivel' => 'integer',
            'paq_cama' => 'integer',
            'cartones' => 'integer',
            'paq_pallet' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
