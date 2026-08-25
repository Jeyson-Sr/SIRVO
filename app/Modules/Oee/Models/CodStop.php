<?php

namespace App\Modules\Oee\Models;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Policies\CodStopPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\Oee\CodStopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog of standardised production stop codes.
 *
 * @property int $id
 * @property string $codigo
 * @property string $detalle
 * @property StopType $tipo_parada
 * @property string|null $categoria
 * @property string|null $causa
 * @property string|null $recurso_afectado
 * @property string|null $familia_oee
 * @property bool $es_tetra_pak
 * @property bool $activo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UsePolicy(CodStopPolicy::class)]
#[Fillable([
    'codigo',
    'detalle',
    'tipo_parada',
    'categoria',
    'causa',
    'recurso_afectado',
    'familia_oee',
    'es_tetra_pak',
    'activo',
])]
class CodStop extends Model
{
    /** @use HasFactory<CodStopFactory> */
    use HasFactory;

    protected static function newFactory(): CodStopFactory
    {
        return CodStopFactory::new();
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
            $query->where('codigo', 'like', "%{$term}%")
                ->orWhere('detalle', 'like', "%{$term}%")
                ->orWhere('categoria', 'like', "%{$term}%");
        });
    }

    /**
     * Get the recorded stops that used this catalog entry.
     *
     * @return HasMany<OeeStopDetail, $this>
     */
    public function stopDetails(): HasMany
    {
        return $this->hasMany(OeeStopDetail::class, 'cod_stop_id');
    }

    /**
     * Determine whether any production hour already used this code.
     */
    public function isInUse(): bool
    {
        return OeeStopDetail::query()
            ->where(function (Builder $query): void {
                $query->where('cod_stop_id', $this->id)
                    ->orWhere('codigo', $this->codigo);
            })
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_parada' => StopType::class,
            'es_tetra_pak' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
