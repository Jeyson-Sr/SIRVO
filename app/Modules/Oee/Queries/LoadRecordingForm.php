<?php

namespace App\Modules\Oee\Queries;

use App\Modules\Oee\Enums\Shift;

/**
 * Shared props for the recording and editing screen.
 */
class LoadRecordingForm
{
    /**
     * @return array{shifts: array<int, array{value: string, label: string}>, lines: array<int, string>, hourRanges: array<string, array<int, string>>, ingeniero: string}
     */
    public function handle(string $engineerName): array
    {
        /** @var array<int, string> $lines */
        $lines = require database_path('data/oee_lines.php');

        return [
            'shifts' => Shift::options(),
            'lines' => $lines,
            'hourRanges' => [
                Shift::Day->value => Shift::Day->hourRanges(),
                Shift::Night->value => Shift::Night->hourRanges(),
            ],
            'ingeniero' => $engineerName,
        ];
    }
}
