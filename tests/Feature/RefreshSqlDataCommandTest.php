<?php

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeStopDetail;

test('it wipes production and sku data and keeps stop codes and users', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $stop = CodStop::factory()->create();
    OeeSku::factory()->create();

    $production = OeeProduction::factory()->for($team)->create([
        'created_by' => $user->id,
    ]);
    $hour = OeeHourDetail::factory()->for($production, 'production')->create();
    OeeStopDetail::factory()->for($hour, 'hour')->forCode($stop)->create();

    $this->artisan('db:refresh', ['--force' => true])->assertSuccessful();

    expect(OeeProduction::query()->count())->toBe(0)
        ->and(OeeHourDetail::query()->count())->toBe(0)
        ->and(OeeStopDetail::query()->count())->toBe(0)
        ->and(OeeSku::query()->count())->toBe(0)
        ->and(CodStop::query()->find($stop->id))->not->toBeNull()
        ->and(User::query()->find($user->id))->not->toBeNull()
        ->and(Team::query()->find($team->id))->not->toBeNull();
});

test('it does nothing when confirmation is declined', function () {
    OeeProduction::factory()->create();

    $this->artisan('db:refresh')
        ->expectsConfirmation('This will delete all production and SKU data. Stop codes, users, and teams stay. Continue?', 'no')
        ->assertSuccessful();

    expect(OeeProduction::query()->count())->toBe(1);
});
