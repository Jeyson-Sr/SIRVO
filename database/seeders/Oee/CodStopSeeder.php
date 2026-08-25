<?php

namespace Database\Seeders\Oee;

use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the plant stop-code catalog.
 *
 * Production cannot be recorded without it: every stop must resolve to a code
 * so its downtime lands in a known OEE loss family.
 */
class CodStopSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the stop code catalog.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $entry) {
            $type = StopType::tryFrom((string) $entry['tipo']);

            if (! $type instanceof StopType) {
                continue;
            }

            CodStop::query()->updateOrCreate(
                ['codigo' => $entry['codigo']],
                [
                    'detalle' => $entry['detalle'],
                    'tipo_parada' => $type,
                    'categoria' => $entry['categoria'],
                    'causa' => $entry['causa'],
                    'recurso_afectado' => $entry['recurso'],
                    'familia_oee' => $entry['familia'],
                    'activo' => $entry['activo'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{codigo: string, detalle: string, tipo: string, categoria: string|null, causa: string|null, recurso: string|null, familia: string|null, activo: bool}>
     */
    private function catalog(): array
    {
        /** @var array<int, array{codigo: string, detalle: string, tipo: string, categoria: string|null, causa: string|null, recurso: string|null, familia: string|null, activo: bool}> $catalog */
        $catalog = require database_path('data/cod_stops.php');

        return $catalog;
    }
}
