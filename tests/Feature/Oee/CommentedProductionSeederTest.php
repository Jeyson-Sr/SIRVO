<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeStopDetail;
use Database\Seeders\Oee\CommentedProductionSeeder;

beforeEach(function () {
    $this->team = Team::factory()->create(['slug' => 'planta-lima']);
    $this->owner = User::factory()->create(['email' => 'test@example.com']);
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);

    foreach ([
        'LINEA 1', 'LINEA 2', 'LINEA 3', 'LINEA 4',
        'LINEA 5', 'LINEA 7', 'LINEA 8', 'LINEA 10',
    ] as $index => $line) {
        OeeSku::factory()->create([
            'sku' => (string) (408400 + $index),
            'linea' => $line,
            'pallets_por_hora' => 20,
            'bph' => 30000,
        ]);
    }

    foreach ([
        ['J36', StopType::Routine],
        ['B10', StopType::Equipment],
        ['B14', StopType::Operational],
        ['J38', StopType::Organizational],
        ['J103', StopType::Planned],
        ['J16', StopType::Quality],
    ] as [$codigo, $type]) {
        CodStop::factory()->ofType($type)->create(['codigo' => $codigo]);
    }
});

test('the commented seeder records twenty closed shifts across several lines', function () {
    (new CommentedProductionSeeder)->run($this->team, $this->owner);

    $productions = OeeProduction::query()->where('team_id', $this->team->id)->get();

    expect($productions)->toHaveCount(20)
        ->and($productions->pluck('linea')->unique()->count())->toBeGreaterThanOrEqual(6)
        ->and($productions->every(fn (OeeProduction $production): bool => $production->closed_at !== null))->toBeTrue()
        ->and($productions->every(fn (OeeProduction $production): bool => str_starts_with($production->op, CommentedProductionSeeder::OP_PREFIX)))->toBeTrue();
});

test('the commented shifts include mnf maintenance quality and stop comments', function () {
    (new CommentedProductionSeeder)->run($this->team, $this->owner);

    expect(OeeHourDetail::query()->whereNotNull('comment_mnf')->count())->toBeGreaterThan(8)
        ->and(OeeHourDetail::query()->whereNotNull('comment_mantto')->count())->toBeGreaterThan(8)
        ->and(OeeHourDetail::query()->whereNotNull('comment_calidad')->count())->toBeGreaterThan(8)
        ->and(OeeStopDetail::query()->whereNotNull('comentario')->count())->toBeGreaterThan(20);
});

test('running the commented seeder again replaces the previous twenty shifts', function () {
    $seeder = new CommentedProductionSeeder;

    $seeder->run($this->team, $this->owner);
    $seeder->run($this->team, $this->owner);

    expect(OeeProduction::query()->where('team_id', $this->team->id)->count())->toBe(20);
});
