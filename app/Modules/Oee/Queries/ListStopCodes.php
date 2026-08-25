<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lists catalog entries for the stop-code admin screen.
 */
class ListStopCodes
{
    /**
     * Number of catalog entries listed per page.
     */
    public const PER_PAGE = 25;

    public function __construct(private PresentStopCode $presentStopCode) {}

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(
        ?string $search,
        ?StopType $tipo,
        ?string $esTetraPak,
        ?string $activo,
    ): LengthAwarePaginator {
        return CodStop::query()
            ->search($search)
            ->when($tipo, fn ($query) => $query->where('tipo_parada', $tipo->value))
            ->when(
                $esTetraPak !== null,
                fn ($query) => $query->where('es_tetra_pak', (bool) $esTetraPak),
            )
            ->when(
                $activo !== null,
                fn ($query) => $query->where('activo', (bool) $activo),
            )
            ->orderBy('codigo')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (CodStop $code): array => $this->presentStopCode->listItem($code));
    }
}
