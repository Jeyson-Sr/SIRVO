<?php

use App\Enums\TeamRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\OeeSection;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeStopDetail;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();

    $this->team = Team::factory()->create();
    $this->member = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->team->members()->attach($this->member, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Paradas->value],
    ]);
});

test('a member can open the stop comments page', function () {
    $this->actingAs($this->member)
        ->get(route('oee.paradas', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('oee/paradas')
            ->has('appliedFilters')
            ->missing('codes'));
});

test('the deferred stop comments arrive on the follow up request', function () {
    $production = OeeProduction::factory()->for($this->team)->on('2026-03-10')->create([
        'linea' => 'L3',
        'sku' => '88001',
        'descripcion' => 'Jugo naranja 500 ml',
    ]);
    $code = CodStop::factory()->create(['codigo' => 'J17', 'detalle' => 'CAMBIO DE SABOR']);
    $hour = OeeHourDetail::factory()->for($production, 'production')->create([
        'closed' => true,
        'hour_range' => '07:30 - 08:30',
    ]);

    OeeStopDetail::factory()->for($hour, 'hour')->forCode($code)->create([
        'tiempo_minutos' => 12,
        'frecuencia' => 1,
        'comentario' => 'Cambio a naranja',
        'descripcion' => 'CAMBIO DE SABOR',
        'registered_at' => '2026-03-10 08:12:00',
    ]);

    $this->actingAs($this->member)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()),
            'X-Inertia-Partial-Component' => 'oee/paradas',
            'X-Inertia-Partial-Data' => 'codes',
        ])
        ->get(route('oee.paradas', $this->team))
        ->assertOk()
        ->assertJsonPath('props.codes.0.codigo', 'J17')
        ->assertJsonPath('props.codes.0.comentarios.0.comentario', 'Cambio a naranja')
        ->assertJsonPath('props.codes.0.comentarios.0.fecha', '2026-03-10')
        ->assertJsonPath('props.codes.0.comentarios.0.hora', '07:30 - 08:30')
        ->assertJsonPath('props.codes.0.productos.0.sku', '88001')
        ->assertJsonPath('props.codes.0.lineas.0.linea', 'L3')
        ->assertJsonPath('props.codes.0.ocurrencias.0.linea', 'L3')
        ->assertJsonPath('props.codes.0.ocurrencias.0.sku', '88001')
        ->assertJsonPath('props.codes.0.ocurrencias.0.totalMinutos', 12)
        ->assertJsonPath('props.codes.0.ocurrencias.0.totalFrecuencia', 1);
});

test('a member without the paradas section cannot open the stop comments page', function () {
    $operator = User::factory()->create();
    $this->team->members()->attach($operator, [
        'role' => TeamRole::Member->value,
        'sections' => [OeeSection::Oee->value],
    ]);

    $this->actingAs($operator)
        ->get(route('oee.paradas', $this->team))
        ->assertForbidden();
});

test('a stranger cannot open the stop comments of a team', function () {
    $this->actingAs($this->stranger)
        ->get(route('oee.paradas', $this->team))
        ->assertForbidden();
});
