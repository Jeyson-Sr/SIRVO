<?php

namespace App\Modules\Oee\Production;

use Illuminate\Validation\ValidationException;

/**
 * Signs off a production run so its figures become historical record.
 */
class CloseProduction
{
    public function handle(OeeProduction $production): void
    {
        $openHours = $production->hours()->where('closed', false)->count();

        if ($openHours > 0) {
            throw ValidationException::withMessages([
                'production' => "Quedan {$openHours} horas sin cerrar en el turno.",
            ]);
        }

        $production->update(['closed_at' => now()]);
    }
}
