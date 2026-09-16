<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeSkuBphChange;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create();
    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
});

test('creating a product records the first bph for that sku and line', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.skus.store', $this->team), skuPayload())
        ->assertRedirect(route('oee.admin.skus.index', $this->team));

    $sku = OeeSku::query()->where('sku', '408462')->where('linea', 'LINEA 1')->first();

    expect($sku)->not->toBeNull()
        ->and((int) $sku->um)->toBe(15)
        ->and((int) $sku->paq_pallet)->toBe(140)
        ->and((float) $sku->pallets_por_hora)->toBe(28.57);

    $change = OeeSkuBphChange::query()->where('oee_sku_id', $sku->id)->first();

    expect($change)->not->toBeNull()
        ->and($change->linea)->toBe('LINEA 1')
        ->and($change->bph_anterior)->toBeNull()
        ->and((float) $change->bph_nuevo)->toBe(60000.0)
        ->and($change->user_id)->toBe($this->admin->id);
});

test('changing the bph of a product records the previous and new values', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.skus.store', $this->team), skuPayload());

    $sku = OeeSku::query()->where('sku', '408462')->first();

    $this->actingAs($this->admin)
        ->put(route('oee.admin.skus.update', [
            'current_team' => $this->team->slug,
            'oeeSku' => $sku->id,
        ]), skuPayload([
            'bph' => '55000',
        ]))
        ->assertRedirect(route('oee.admin.skus.index', $this->team));

    $sku->refresh();

    expect((float) $sku->bph)->toBe(55000.0)
        ->and((float) $sku->pallets_por_hora)->toBe(26.19)
        ->and(OeeSkuBphChange::query()->where('oee_sku_id', $sku->id)->count())->toBe(2);

    $latest = OeeSkuBphChange::query()->where('oee_sku_id', $sku->id)->latest('id')->first();

    expect((float) $latest->bph_anterior)->toBe(60000.0)
        ->and((float) $latest->bph_nuevo)->toBe(55000.0)
        ->and($latest->linea)->toBe('LINEA 1');
});

test('the same sku can run on two lines with a different bph', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.skus.store', $this->team), skuPayload())
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post(route('oee.admin.skus.store', $this->team), skuPayload([
            'linea' => 'LINEA 8',
            'bph' => '36000',
            'um' => '6',
            'nivel' => '8',
            'paq_cama' => '16',
        ]))
        ->assertRedirect();

    expect(OeeSku::query()->where('sku', '408462')->count())->toBe(2);

    $line8 = OeeSku::query()->where('sku', '408462')->where('linea', 'LINEA 8')->first();

    expect($line8)->not->toBeNull()
        ->and((int) $line8->paq_pallet)->toBe(128)
        ->and((float) $line8->pallets_por_hora)->toBe(46.88);
});

test('the edit form includes the bph history of the product', function () {
    $this->actingAs($this->admin)
        ->post(route('oee.admin.skus.store', $this->team), skuPayload());

    $sku = OeeSku::query()->where('sku', '408462')->first();

    $this->actingAs($this->admin)
        ->get(route('oee.admin.skus.edit', [
            'current_team' => $this->team->slug,
            'oeeSku' => $sku->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/skus/edit')
            ->has('bphChanges', 1)
            ->where('bphChanges.0.linea', 'LINEA 1')
            ->where('bphChanges.0.bphNuevo', 60000)
            ->where('sku.um', '15')
            ->where('sku.paq_pallet', '140'));
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function skuPayload(array $overrides = []): array
{
    return [
        'sku' => '408462',
        'linea' => 'LINEA 1',
        'descripcion' => 'CIELO AGUA 625 ml',
        'formato' => '0.625',
        'marca' => 'CIELO',
        'sabor' => 'AGUA',
        'um' => '15',
        'bph' => '60000',
        'compania' => 'AJE CARAL',
        'mercado' => 'PERU',
        'nivel' => '7',
        'paq_cama' => '20',
        'cartones' => '7',
        'activo' => '1',
        ...$overrides,
    ];
}
