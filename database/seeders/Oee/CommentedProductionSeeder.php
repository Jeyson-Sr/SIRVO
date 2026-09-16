<?php

namespace Database\Seeders\Oee;

use App\Models\Team;
use App\Models\User;
use App\Modules\Oee\Enums\Shift;
use App\Modules\Oee\Enums\StopType;
use App\Modules\Oee\Models\CodStop;
use App\Modules\Oee\Models\OeeHourDetail;
use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use App\Modules\Oee\Models\OeeStopDetail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Twenty closed shifts with MNF, maintenance and quality comments.
 *
 * Built so the Paradas report and the hour sheet have something to show:
 * each run uses a real SKU on its line and real stop codes, and the
 * downtime minutes match the pallets that were not produced.
 */
class CommentedProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public const OP_PREFIX = '202680';

    /**
     * People who appear on the commented demo sheets.
     *
     * @var array<int, string>
     */
    private const ENGINEERS = ['R. Quispe', 'M. Salazar', 'J. Ccahuana', 'L. Ramírez'];

    /**
     * @var array<int, string>
     */
    private const OPERATORS = ['A. Huamán', 'C. Torres', 'D. Flores', 'S. Mamani', 'P. Vargas'];

    /**
     * Seed twenty commented shifts for the given plant.
     */
    public function run(?Team $team = null, ?User $creator = null): void
    {
        $team ??= Team::query()->where('slug', 'planta-lima')->first();
        $creator ??= User::query()->where('email', 'test@example.com')->first();

        if (! $team instanceof Team) {
            throw new RuntimeException('No hay un equipo planta-lima para sembrar turnos comentados.');
        }

        $catalog = CodStop::query()->active()->get()->keyBy('codigo');

        if ($catalog->isEmpty()) {
            throw new RuntimeException('El catálogo de paradas está vacío. Ejecuta CodStopSeeder primero.');
        }

        $skus = OeeSku::query()->active()->orderBy('linea')->orderBy('sku')->get();

        if ($skus->isEmpty()) {
            throw new RuntimeException('El catálogo de SKU está vacío. Ejecuta SkuSeeder primero.');
        }

        $today = CarbonImmutable::today();

        DB::transaction(function () use ($team, $creator, $catalog, $skus, $today) {
            $this->forgetPrevious($team);

            foreach ($this->scenarios() as $index => $scenario) {
                $sku = $this->skuFor($skus, $scenario['linea'], $scenario['sku'] ?? null);

                $this->seedShift(
                    $team,
                    $creator,
                    $today->subDays($scenario['days_ago']),
                    $sku,
                    $scenario['shift'],
                    $catalog,
                    self::OP_PREFIX.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    $scenario['events'],
                );
            }
        });
    }

    /**
     * Twenty runs on distinct lines, mixes of equipment, maintenance and quality.
     *
     * @return array<int, array{linea: string, sku?: string, shift: Shift, days_ago: int, events: array<int, array<string, mixed>>}>
     */
    private function scenarios(): array
    {
        return [
            [
                'linea' => 'LINEA 1',
                'sku' => '408462',
                'shift' => Shift::Day,
                'days_ago' => 1,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 8, 'comentario' => 'Arranque de turno. Primera hora hasta primer pallet bueno.'],
                        ],
                        'mnf' => 'Inicio de CIELO 625 ml. Velocidad estable después del arranque.',
                    ],
                    3 => [
                        'stops' => [
                            ['codigo' => 'B10', 'minutos' => 18, 'comentario' => 'Torpedo gastado en estación 4. Mantenimiento lo cambió y se reanudó.'],
                        ],
                        'mantto' => 'Cambio de torpedo estación 4. Queda pendiente revisar estaciones 5 y 6.',
                        'mnf' => 'Se escaló a mantenimiento. Línea parada 18 min.',
                    ],
                    7 => [
                        'stops' => [
                            ['codigo' => 'J38', 'minutos' => 12, 'comentario' => 'Tapas no cierran hermético. Se cambió el lote de cajas.'],
                        ],
                        'calidad' => 'Calidad retuvo 2 pallets por fuga de tapa. Lote de tapas observado.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 2',
                'sku' => '408469',
                'shift' => Shift::Night,
                'days_ago' => 1,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 10, 'comentario' => 'Arranque nocturno de CIELO 1 L. Sopladora tardó en estabilizar.'],
                        ],
                        'mnf' => 'Arranque lento. Primeros 10 min sin pallet bueno.',
                    ],
                    4 => [
                        'stops' => [
                            ['codigo' => 'J103', 'minutos' => 22, 'comentario' => 'Preventivo de sopladora programado por mantto. Se adelantó al turno noche.'],
                        ],
                        'mantto' => 'Preventivo de sopladora: engrase y revisión de moldes. OK para seguir.',
                    ],
                    9 => [
                        'stops' => [
                            ['codigo' => 'J16', 'minutos' => 9, 'comentario' => 'Baja de producto por brix un punto bajo. Se ajustó jarabe.'],
                        ],
                        'calidad' => 'Brix 0.2 bajo spec. Se corrigió en tanque y se liberó la línea.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 3',
                'sku' => '421937',
                'shift' => Shift::Day,
                'days_ago' => 2,
                'events' => [
                    1 => [
                        'stops' => [
                            ['codigo' => 'J100', 'minutos' => 14, 'comentario' => 'Cambio de sabor a acaí. 5 pasos de saneamiento.'],
                        ],
                        'mnf' => 'Cambio de sabor BIO AMAYU. Arranque limpio después del CIP.',
                    ],
                    5 => [
                        'stops' => [
                            ['codigo' => 'J95', 'minutos' => 16, 'comentario' => 'Producto no conforme: sabor extraño en las primeras botellas post cambio.'],
                        ],
                        'calidad' => 'Calidad retuvo las primeras 4 cajas. Se descartó y se reinició con OK sensorial.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 4',
                'sku' => '421790',
                'shift' => Shift::Day,
                'days_ago' => 2,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 7, 'comentario' => 'Arranque SPORADE tropical. Etiquetadora desfasada 3 min.'],
                        ],
                    ],
                    2 => [
                        'stops' => [
                            ['codigo' => 'B3', 'minutos' => 15, 'comentario' => 'Falla de sensor en etiquetadora. Se limpió y se recalibró.'],
                        ],
                        'mantto' => 'Sensor de etiquetadora sucio por jarabe. Se limpió y quedó en 0 fallas.',
                        'mnf' => 'Se avisó a MNF por parada de etiquetadora.',
                    ],
                    8 => [
                        'stops' => [
                            ['codigo' => 'J1', 'minutos' => 11, 'comentario' => 'Etiqueta se arruga. Lote de bobina con adhesivo seco.'],
                        ],
                        'calidad' => 'Se cambió bobina. Etiquetas del lote 12-A observadas.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 5',
                'sku' => '421766',
                'shift' => Shift::Night,
                'days_ago' => 3,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 9, 'comentario' => 'Arranque BIG COLA. Llenadora con espuma al inicio.'],
                        ],
                    ],
                    6 => [
                        'stops' => [
                            ['codigo' => 'B7', 'minutos' => 20, 'comentario' => 'Rotura de faja en transportador de salida. Mantto cambió el tramo.'],
                        ],
                        'mantto' => 'Faja de salida partida. Se instaló el spare y se tensó.',
                        'mnf' => 'Parada de 20 min. Se reportó a MNF y se pidió faja de respaldo.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 7',
                'sku' => '423551',
                'shift' => Shift::Day,
                'days_ago' => 3,
                'events' => [
                    2 => [
                        'stops' => [
                            ['codigo' => 'J19', 'minutos' => 18, 'comentario' => 'Prueba de mantto en llenadora: ajuste de válvulas.'],
                        ],
                        'mantto' => 'Ajuste de válvulas 2 y 5. Quedó sin goteo.',
                    ],
                    6 => [
                        'stops' => [
                            ['codigo' => 'J16', 'minutos' => 8, 'comentario' => 'Baja de CIELO pera por color fuera de rango.'],
                        ],
                        'calidad' => 'Color un tono oscuro. Se diluyó tanque y se liberó.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 8',
                'sku' => '408462',
                'shift' => Shift::Day,
                'days_ago' => 4,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 12, 'comentario' => 'Arranque LINEA 8 con CIELO 625. BPH más bajo que LINEA 1.'],
                        ],
                        'mnf' => 'Misma SKU que L1 pero BPH 30k. Se confirmó receta de línea.',
                    ],
                    4 => [
                        'stops' => [
                            ['codigo' => 'B11', 'minutos' => 17, 'comentario' => 'Rodamientos gastados en sopladora. Se cambió el juego.'],
                        ],
                        'mantto' => 'Cambio de rodamientos sopladora. Ruido desapareció.',
                    ],
                    10 => [
                        'stops' => [
                            ['codigo' => 'J95', 'minutos' => 10, 'comentario' => 'Botellas deformadas. Calidad paró para revisar horno.'],
                        ],
                        'calidad' => 'Preforma sobrecalentada. Se bajó 3 °C y se liberó.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 10',
                'sku' => '422672',
                'shift' => Shift::Night,
                'days_ago' => 4,
                'events' => [
                    1 => [
                        'stops' => [
                            ['codigo' => 'B6', 'minutos' => 7, 'comentario' => 'Traba de botella VOLT en sinfín. Se despejó y se reguló.'],
                        ],
                        'mnf' => 'Traba puntual. Operador despejó sin escalar.',
                    ],
                    5 => [
                        'stops' => [
                            ['codigo' => 'J104', 'minutos' => 28, 'comentario' => 'Mantenimiento mayor de paletizadora. Se aprovechó el hueco de programa.'],
                        ],
                        'mantto' => 'Mayor de paletizadora: cambio de pinza y revisión de ejes.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 1',
                'sku' => '424046',
                'shift' => Shift::Night,
                'days_ago' => 5,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 6, 'comentario' => 'Arranque KR kolita 400 ml.'],
                        ],
                    ],
                    3 => [
                        'stops' => [
                            ['codigo' => 'B14', 'minutos' => 19, 'comentario' => 'Cambio de formato de 625 a 400. Regulaciones de llenadora y etiquetadora.'],
                        ],
                        'mnf' => 'Cambio de formato pedido por planificación. Tardó 19 min.',
                    ],
                    8 => [
                        'stops' => [
                            ['codigo' => 'J38', 'minutos' => 13, 'comentario' => 'Tapas KR no enroscan. Se devolvió media caja al almacén.'],
                        ],
                        'calidad' => 'Lote de tapas 4481 observado. Se cambió a lote 4487.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 2',
                'sku' => '408503',
                'shift' => Shift::Day,
                'days_ago' => 5,
                'events' => [
                    2 => [
                        'stops' => [
                            ['codigo' => 'A1', 'minutos' => 8, 'comentario' => 'Calibración del aplicador de asas. Pack de 6 desalineado.'],
                        ],
                        'mantto' => 'Calibración de asas. Quedó centrado.',
                    ],
                    7 => [
                        'stops' => [
                            ['codigo' => 'J16', 'minutos' => 11, 'comentario' => 'Baja por conductividad alta en agua. Calidad pidió flush.'],
                        ],
                        'calidad' => 'Flush de 8 min. Conductividad volvió a rango.',
                        'mnf' => 'Se coordinó con tratamiento de agua.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 4',
                'sku' => '421942',
                'shift' => Shift::Night,
                'days_ago' => 6,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 8, 'comentario' => 'Arranque SPORADE uva en LINEA 4.'],
                        ],
                    ],
                    4 => [
                        'stops' => [
                            ['codigo' => 'B10', 'minutos' => 14, 'comentario' => 'Otro torpedo gastado, ahora estación 2.'],
                        ],
                        'mantto' => 'Segundo cambio de torpedo en la semana. Pedir stock extra.',
                    ],
                    8 => [
                        'stops' => [
                            ['codigo' => 'J95', 'minutos' => 12, 'comentario' => 'Color uva más claro. Calidad comparó contra estándar.'],
                        ],
                        'calidad' => 'Se ajustó dosificación de color. Lote liberado a las 02:10.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 5',
                'sku' => '421832',
                'shift' => Shift::Day,
                'days_ago' => 6,
                'events' => [
                    1 => [
                        'stops' => [
                            ['codigo' => 'J100', 'minutos' => 15, 'comentario' => 'Cambio de BIG COLA a CIFRUT citrus. 5 pasos.'],
                        ],
                        'mnf' => 'Cambio de sabor. Primeros pallets con OK de calidad.',
                    ],
                    6 => [
                        'stops' => [
                            ['codigo' => 'B4', 'minutos' => 9, 'comentario' => 'Cambio de cuchilla en encajonadora. Corte irregular.'],
                        ],
                        'mantto' => 'Cuchilla nueva instalada. Corte limpio.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 8',
                'sku' => '408705',
                'shift' => Shift::Night,
                'days_ago' => 7,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 11, 'comentario' => 'Arranque CIELO con gas. Carbonatación inestable 11 min.'],
                        ],
                        'calidad' => 'Volumen de CO2 bajo al inicio. Se esperó setpoint y se liberó.',
                    ],
                    5 => [
                        'stops' => [
                            ['codigo' => 'J103', 'minutos' => 20, 'comentario' => 'Preventivo de carbonatador. Se adelantó por fuga menor.'],
                        ],
                        'mantto' => 'Preventivo carbonatador: sello cambiado. Sin fuga.',
                        'mnf' => 'Parada coordinada con mantto. No hubo producto no conforme.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 3',
                'sku' => '421938',
                'shift' => Shift::Night,
                'days_ago' => 7,
                'events' => [
                    3 => [
                        'stops' => [
                            ['codigo' => 'J1', 'minutos' => 10, 'comentario' => 'Etiqueta BIO camu camu se despega en las esquinas.'],
                        ],
                        'calidad' => 'Adhesivo débil. Se cambió bobina y se retuvo 1 pallet.',
                    ],
                    8 => [
                        'stops' => [
                            ['codigo' => 'B6', 'minutos' => 6, 'comentario' => 'Traba de botella en bajada. Formato 600 ml sensible.'],
                        ],
                        'mnf' => 'Se reguló guía. No se repitió en la hora.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 10',
                'sku' => '422871',
                'shift' => Shift::Day,
                'days_ago' => 8,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 13, 'comentario' => 'Arranque VOLT fantasy. Llenadora chica, arranque largo.'],
                        ],
                    ],
                    4 => [
                        'stops' => [
                            ['codigo' => 'B3', 'minutos' => 16, 'comentario' => 'Sensor de tapa no lee. Cable suelto.'],
                        ],
                        'mantto' => 'Se rearmó conector del sensor. Quedó estable.',
                    ],
                    9 => [
                        'stops' => [
                            ['codigo' => 'J16', 'minutos' => 7, 'comentario' => 'Baja por tapa cruzada. Calidad pidió inspección 100%.'],
                        ],
                        'calidad' => 'Inspección 100% por 7 min. Se encontraron 4 tapas cruzadas.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 1',
                'sku' => '423276',
                'shift' => Shift::Day,
                'days_ago' => 8,
                'events' => [
                    2 => [
                        'stops' => [
                            ['codigo' => 'J19', 'minutos' => 12, 'comentario' => 'Mantto midió desgaste de estrella de llenadora.'],
                        ],
                        'mantto' => 'Estrella aún en tolerancia. Revisión en el próximo preventivo.',
                    ],
                    7 => [
                        'stops' => [
                            ['codigo' => 'J38', 'minutos' => 14, 'comentario' => 'Tapas BIG kolita con rebaba. No asientan.'],
                        ],
                        'calidad' => 'Se rechazó caja de tapas. Nuevo lote OK.',
                        'mnf' => 'Se avisó a almacén para no devolver ese lote a L1.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 4',
                'sku' => '422089',
                'shift' => Shift::Day,
                'days_ago' => 9,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 8, 'comentario' => 'Arranque BIO aloe uva.'],
                        ],
                    ],
                    5 => [
                        'stops' => [
                            ['codigo' => 'J104', 'minutos' => 24, 'comentario' => 'Mantenimiento mayor de etiquetadora. Cambio de cabezal.'],
                        ],
                        'mantto' => 'Cabezal nuevo. Prueba de 20 etiquetas OK.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 5',
                'sku' => '421833',
                'shift' => Shift::Night,
                'days_ago' => 9,
                'events' => [
                    1 => [
                        'stops' => [
                            ['codigo' => 'B14', 'minutos' => 16, 'comentario' => 'Regulación post cambio a fruit punch 350 ml.'],
                        ],
                        'mnf' => 'Cambio de formato. Primer pallet con OK de calidad.',
                    ],
                    6 => [
                        'stops' => [
                            ['codigo' => 'J95', 'minutos' => 11, 'comentario' => 'Sabor punch más ácido. Calidad comparó contra retención.'],
                        ],
                        'calidad' => 'Acidez en límite alto. Se aceptó el lote y se avisó a proceso.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 7',
                'sku' => '423552',
                'shift' => Shift::Night,
                'days_ago' => 10,
                'events' => [
                    0 => [
                        'stops' => [
                            ['codigo' => 'J36', 'minutos' => 9, 'comentario' => 'Arranque CIELO manzana en L7.'],
                        ],
                    ],
                    3 => [
                        'stops' => [
                            ['codigo' => 'B7', 'minutos' => 13, 'comentario' => 'Faja de acumulador se salió. Se centró y se tensó.'],
                        ],
                        'mantto' => 'Faja recentrada. Vigilar en el siguiente turno.',
                    ],
                    8 => [
                        'stops' => [
                            ['codigo' => 'J1', 'minutos' => 8, 'comentario' => 'Etiqueta manzana con corte chueco de fábrica.'],
                        ],
                        'calidad' => 'Se cambió bobina. Se fotografió el defecto para reclamo.',
                    ],
                ],
            ],
            [
                'linea' => 'LINEA 8',
                'sku' => '408503',
                'shift' => Shift::Day,
                'days_ago' => 10,
                'events' => [
                    2 => [
                        'stops' => [
                            ['codigo' => 'A1', 'minutos' => 7, 'comentario' => 'Asas de pack 6 se caían. Recalibración corta.'],
                        ],
                        'mantto' => 'Aplicador de asas recalibrado.',
                    ],
                    6 => [
                        'stops' => [
                            ['codigo' => 'J16', 'minutos' => 10, 'comentario' => 'Baja de CIELO por partículas en filtro. Se cambió cartucho.'],
                        ],
                        'calidad' => 'Filtro de pulido saturado. Después del cambio el agua salió clara.',
                        'mnf' => 'Se pidió stock de cartuchos para L8.',
                    ],
                    9 => [
                        'stops' => [
                            ['codigo' => 'J18', 'minutos' => 15, 'comentario' => 'Refrigerio de personal. Línea detenida 15 min.'],
                        ],
                        'mnf' => 'Refrigerio de turno día. Se reanudó a la hora.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, OeeSku>  $skus
     */
    private function skuFor(Collection $skus, string $linea, ?string $preferredSku): OeeSku
    {
        $onLine = $skus->where('linea', $linea);

        $sku = $preferredSku !== null
            ? $onLine->firstWhere('sku', $preferredSku)
            : null;

        $sku ??= $onLine->first();

        if (! $sku instanceof OeeSku) {
            throw new RuntimeException("No hay un SKU activo para {$linea}.");
        }

        return $sku;
    }

    /**
     * @param  Collection<string, CodStop>  $catalog
     * @param  array<int, array<string, mixed>>  $events
     */
    private function seedShift(
        Team $team,
        ?User $creator,
        CarbonImmutable $date,
        OeeSku $sku,
        Shift $shift,
        Collection $catalog,
        string $op,
        array $events,
    ): void {
        $production = OeeProduction::query()->create([
            'team_id' => $team->id,
            'created_by' => $creator?->id,
            'fecha' => $date->toDateString(),
            'turno' => $shift,
            'linea' => $sku->linea,
            'op' => $op,
            'ingeniero' => self::ENGINEERS[$this->stableIndex($op, count(self::ENGINEERS))],
            'operador' => self::OPERATORS[$this->stableIndex($op.'op', count(self::OPERATORS))],
            'sku' => $sku->sku,
            'descripcion' => $sku->descripcion,
            'formato' => $sku->formato,
            'marca' => $sku->marca,
            'sabor' => $sku->sabor,
            'pallets_por_hora' => $sku->pallets_por_hora,
            'bph' => $sku->bph,
            'closed_at' => $date->setTime(23, 0),
        ]);

        $target = (float) $sku->pallets_por_hora;

        foreach ($shift->hourRanges() as $index => $range) {
            $event = $events[$index] ?? [];
            $stops = $event['stops'] ?? [];
            $downtime = array_sum(array_map(
                fn (array $stop): float => (float) $stop['minutos'],
                $stops,
            ));
            $produced = round($target * (OeeHourDetail::MINUTES_PER_HOUR - $downtime) / OeeHourDetail::MINUTES_PER_HOUR, 2);

            $hour = OeeHourDetail::query()->create([
                'oee_production_id' => $production->id,
                'hour_index' => $index,
                'hour_range' => $range,
                'duration_minutes' => OeeHourDetail::MINUTES_PER_HOUR,
                'sku' => $sku->sku,
                'formato' => $sku->formato,
                'pallets_por_hora' => $sku->pallets_por_hora,
                'bph' => $sku->bph,
                'estimado' => $target,
                'producido' => $produced,
                'closed' => true,
                'closed_at' => $date->setTime(8, 0)->addHours($index),
                'comment_mnf' => $event['mnf'] ?? null,
                'comment_mantto' => $event['mantto'] ?? null,
                'comment_calidad' => $event['calidad'] ?? null,
            ]);

            foreach ($stops as $stop) {
                $code = $this->codeFor($catalog, $stop['codigo']);

                OeeStopDetail::query()->create([
                    'oee_hour_detail_id' => $hour->id,
                    'cod_stop_id' => $code->id,
                    'client_uuid' => (string) Str::uuid(),
                    'codigo' => $code->codigo,
                    'tipo' => $code->tipo_parada,
                    'descripcion' => $code->detalle,
                    'comentario' => $stop['comentario'],
                    'tiempo_minutos' => $stop['minutos'],
                    'frecuencia' => 1,
                    'registered_at' => $date->setTime(8, 0)->addHours($index),
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, CodStop>  $catalog
     */
    private function codeFor(Collection $catalog, string $codigo): CodStop
    {
        $code = $catalog->get($codigo);

        if ($code instanceof CodStop) {
            return $code;
        }

        $fallback = match ($codigo) {
            'J103', 'J104', 'J19', 'J18' => StopType::Planned,
            'J16', 'J95' => StopType::Quality,
            'J36', 'J100' => StopType::Routine,
            'B14', 'B6' => StopType::Operational,
            'J38', 'J1' => StopType::Organizational,
            default => StopType::Equipment,
        };

        $code = $catalog->first(
            fn (CodStop $candidate): bool => $candidate->tipo_parada === $fallback,
        );

        if (! $code instanceof CodStop) {
            throw new RuntimeException("No hay un código de parada para {$codigo}.");
        }

        return $code;
    }

    private function forgetPrevious(Team $team): void
    {
        OeeProduction::query()
            ->where('team_id', $team->id)
            ->where('op', 'like', self::OP_PREFIX.'%')
            ->delete();
    }

    private function stableIndex(string $seed, int $count): int
    {
        return abs(crc32($seed)) % $count;
    }
}
