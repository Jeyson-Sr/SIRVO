<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Models\OeeSku;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->member = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->team->members()->attach($this->member, ['role' => TeamRole::Admin->value]);
});

test('an admin can search the sku catalog by code or brand on a line', function () {
    OeeSku::factory()->create([
        'sku' => '408462',
        'linea' => 'LINEA 1',
        'descripcion' => 'CIELO AGUA 625 ml',
        'marca' => 'CIELO',
        'pallets_por_hora' => 28.6,
        'bph' => 60000,
    ]);
    OeeSku::factory()->create([
        'sku' => '422783',
        'linea' => 'LINEA 5',
        'descripcion' => 'VOLT GAMER 300 ml',
        'marca' => 'VOLT',
    ]);

    $byCode = $this->actingAs($this->member)->getJson(
        route('oee.skus.index', [
            'current_team' => $this->team->slug,
            'linea' => 'LINEA 1',
            'search' => '408462',
        ]),
    );

    $byCode->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', '408462')
        ->assertJsonPath('data.0.palletsPorHora', 28.6);

    $byBrand = $this->actingAs($this->member)->getJson(
        route('oee.skus.index', [
            'current_team' => $this->team->slug,
            'linea' => 'LINEA 5',
            'search' => 'volt',
        ]),
    );

    $byBrand->assertOk()->assertJsonPath('data.0.sku', '422783');
});

test('searching a line only returns products that run on that line', function () {
    OeeSku::factory()->create(['sku' => '408462', 'linea' => 'LINEA 1', 'marca' => 'CIELO']);
    OeeSku::factory()->create(['sku' => '408469', 'linea' => 'LINEA 2', 'marca' => 'CIELO']);

    $response = $this->actingAs($this->member)->getJson(
        route('oee.skus.index', [
            'current_team' => $this->team->slug,
            'linea' => 'LINEA 1',
            'search' => 'cielo',
        ]),
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', '408462');
});

test('searching without a line returns no products', function () {
    OeeSku::factory()->create(['sku' => '408462', 'linea' => 'LINEA 1']);

    $response = $this->actingAs($this->member)->getJson(
        route('oee.skus.index', [
            'current_team' => $this->team->slug,
            'search' => '408462',
        ]),
    );

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('retired skus are not offered for selection', function () {
    OeeSku::factory()->inactive()->create([
        'sku' => '999999',
        'linea' => 'LINEA 1',
        'descripcion' => 'Retirado',
    ]);

    $response = $this->actingAs($this->member)->getJson(
        route('oee.skus.index', [
            'current_team' => $this->team->slug,
            'linea' => 'LINEA 1',
            'search' => '999999',
        ]),
    );

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('a stranger cannot browse the sku catalog', function () {
    $response = $this->actingAs($this->stranger)->getJson(
        route('oee.skus.index', ['current_team' => $this->team->slug, 'linea' => 'LINEA 1']),
    );

    $response->assertForbidden();
});
