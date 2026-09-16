<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();

    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->engineer = User::factory()->create();
    $this->operator = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
    $this->team->members()->attach($this->engineer, ['role' => TeamRole::Engineer->value]);
    $this->team->members()->attach($this->operator, ['role' => TeamRole::Member->value]);
});

/**
 * @param  array<string, mixed>  $parameters
 */
function skuAdminRoute(string $name, Team $team, array $parameters = []): string
{
    return route($name, ['current_team' => $team->slug, ...$parameters]);
}

test('an owner can open the product catalog', function () {
    OeeSku::factory()->create([
        'sku' => '408462',
        'linea' => 'LINEA 1',
        'descripcion' => 'CIELO AGUA 625 ml',
        'pallets_por_hora' => 28.6,
        'bph' => 60000,
        'formato' => '0.625',
    ]);

    $this->actingAs($this->owner)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/skus/index')
            ->has('lines', 14)
            ->has('skus.data', 1)
            ->where('skus.data.0.sku', '408462')
            ->where('skus.data.0.linea', 'LINEA 1')
            ->where('skus.data.0.palletsPorHora', 28.6));
});

test('an admin can open the product catalog', function () {
    $this->actingAs($this->admin)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/skus/index'));
});

test('an engineer cannot open the product catalog', function () {
    $this->actingAs($this->engineer)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertForbidden();
});

test('an operator cannot open the product catalog', function () {
    $this->actingAs($this->operator)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertForbidden();
});

test('a visor with the productos section can browse but not create', function () {
    $this->team->memberships()
        ->where('user_id', $this->operator->id)
        ->update(['sections' => [OeeSection::Skus->value]]);

    $this->actingAs($this->operator)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(skuAdminRoute('oee.admin.skus.create', $this->team))
        ->assertForbidden();
});

test('a stranger cannot open the product catalog', function () {
    $this->actingAs($this->stranger)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team))
        ->assertForbidden();
});

test('the catalog can be filtered by line', function () {
    OeeSku::factory()->create(['sku' => '408462', 'linea' => 'LINEA 1']);
    OeeSku::factory()->create(['sku' => '422783', 'linea' => 'LINEA 5']);

    $this->actingAs($this->admin)
        ->get(skuAdminRoute('oee.admin.skus.index', $this->team, [
            'linea' => 'LINEA 1',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/admin/skus/index')
            ->has('skus.data', 1)
            ->where('skus.data.0.sku', '408462')
            ->where('filters.linea', 'LINEA 1'));
});

test('an admin can create a product for a line', function () {
    $this->actingAs($this->admin)
        ->post(skuAdminRoute('oee.admin.skus.store', $this->team), [
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
        ])
        ->assertRedirect(skuAdminRoute('oee.admin.skus.index', $this->team));

    $sku = OeeSku::query()->where('sku', '408462')->first();

    expect($sku)->not->toBeNull()
        ->and($sku->linea)->toBe('LINEA 1')
        ->and($sku->descripcion)->toBe('CIELO AGUA 625 ml')
        ->and((int) $sku->um)->toBe(15)
        ->and((int) $sku->paq_pallet)->toBe(140)
        ->and((float) $sku->pallets_por_hora)->toBe(28.57)
        ->and((float) $sku->bph)->toBe(60000.0)
        ->and($sku->formato)->toBe('0.625')
        ->and($sku->activo)->toBeTrue();
});

test('an operator cannot create a product', function () {
    $this->actingAs($this->operator)
        ->post(skuAdminRoute('oee.admin.skus.store', $this->team), [
            'sku' => '999001',
            'linea' => 'LINEA 1',
            'descripcion' => 'No debería crearse',
            'formato' => '0.5',
            'um' => '12',
            'bph' => '10000',
            'nivel' => '6',
            'paq_cama' => '16',
            'cartones' => '6',
            'activo' => '1',
        ])
        ->assertForbidden();

    expect(OeeSku::query()->where('sku', '999001')->exists())->toBeFalse();
});

test('an admin can change ph bph content and line', function () {
    $sku = OeeSku::factory()->create([
        'sku' => '408462',
        'linea' => 'LINEA 1',
        'formato' => '0.625',
        'pallets_por_hora' => 28.6,
        'bph' => 60000,
    ]);

    $this->actingAs($this->admin)
        ->put(skuAdminRoute('oee.admin.skus.update', $this->team, ['oeeSku' => $sku->id]), [
            'sku' => '408462',
            'linea' => 'LINEA 2',
            'descripcion' => 'CIELO AGUA 625 ml',
            'formato' => '0.5',
            'marca' => 'CIELO',
            'sabor' => 'AGUA',
            'um' => '15',
            'bph' => '62000',
            'compania' => 'AJE CARAL',
            'mercado' => 'PERU',
            'nivel' => '7',
            'paq_cama' => '20',
            'cartones' => '7',
            'activo' => '1',
        ])
        ->assertRedirect(skuAdminRoute('oee.admin.skus.index', $this->team));

    $sku->refresh();

    expect($sku->linea)->toBe('LINEA 2')
        ->and($sku->formato)->toBe('0.5')
        ->and((int) $sku->paq_pallet)->toBe(140)
        ->and((float) $sku->pallets_por_hora)->toBe(29.52)
        ->and((float) $sku->bph)->toBe(62000.0);
});

test('an unused product can be deleted', function () {
    $sku = OeeSku::factory()->create(['sku' => 'DEL001']);

    $this->actingAs($this->owner)
        ->delete(skuAdminRoute('oee.admin.skus.destroy', $this->team, ['oeeSku' => $sku->id]))
        ->assertRedirect(skuAdminRoute('oee.admin.skus.index', $this->team));

    expect(OeeSku::query()->whereKey($sku->id)->exists())->toBeFalse();
});

test('a product already used on a shift cannot be deleted', function () {
    $sku = OeeSku::factory()->create(['sku' => 'USED01']);
    OeeProduction::factory()->for($this->team)->create(['sku' => $sku->sku]);

    $this->actingAs($this->owner)
        ->from(skuAdminRoute('oee.admin.skus.edit', $this->team, ['oeeSku' => $sku->id]))
        ->delete(skuAdminRoute('oee.admin.skus.destroy', $this->team, ['oeeSku' => $sku->id]))
        ->assertRedirect(skuAdminRoute('oee.admin.skus.edit', $this->team, ['oeeSku' => $sku->id]))
        ->assertSessionHasErrors('sku');

    expect(OeeSku::query()->whereKey($sku->id)->exists())->toBeTrue();
});

test('creating a product requires a unique sku on that line and calculation values', function () {
    OeeSku::factory()->create(['sku' => 'DUP001', 'linea' => 'LINEA 1']);

    $this->actingAs($this->admin)
        ->from(skuAdminRoute('oee.admin.skus.create', $this->team))
        ->post(skuAdminRoute('oee.admin.skus.store', $this->team), [
            'sku' => 'DUP001',
            'linea' => 'NOPE',
            'descripcion' => '',
            'um' => '',
            'bph' => '',
            'nivel' => '',
            'paq_cama' => '',
            'cartones' => '',
            'activo' => '1',
        ])
        ->assertRedirect(skuAdminRoute('oee.admin.skus.create', $this->team))
        ->assertSessionHasErrors(['linea', 'descripcion', 'um', 'bph', 'nivel', 'paq_cama', 'cartones']);

    $this->actingAs($this->admin)
        ->from(skuAdminRoute('oee.admin.skus.create', $this->team))
        ->post(skuAdminRoute('oee.admin.skus.store', $this->team), [
            'sku' => 'DUP001',
            'linea' => 'LINEA 1',
            'descripcion' => 'Duplicado',
            'um' => '15',
            'bph' => '60000',
            'nivel' => '7',
            'paq_cama' => '20',
            'cartones' => '7',
            'activo' => '1',
        ])
        ->assertRedirect(skuAdminRoute('oee.admin.skus.create', $this->team))
        ->assertSessionHasErrors('sku');
});
