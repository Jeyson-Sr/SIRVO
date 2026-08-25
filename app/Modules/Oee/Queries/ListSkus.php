<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Models\OeeSku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lists finished goods for the product admin screen.
 */
class ListSkus
{
    /**
     * Number of catalog entries listed per page.
     */
    public const PER_PAGE = 25;

    public function __construct(private PresentSku $presentSku) {}

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(?string $search, ?string $linea, ?string $activo): LengthAwarePaginator
    {
        return OeeSku::query()
            ->search($search)
            ->when($linea, fn ($query) => $query->where('linea', $linea))
            ->when(
                $activo !== null,
                fn ($query) => $query->where('activo', (bool) $activo),
            )
            ->orderBy('linea')
            ->orderBy('sku')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (OeeSku $sku): array => $this->presentSku->listItem($sku));
    }
}
