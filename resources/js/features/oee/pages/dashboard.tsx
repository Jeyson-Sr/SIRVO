import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, ListChecks } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartSkeleton,
    SummarySkeleton,
} from '@/features/oee/components/chart-skeleton';
import { DashboardFilters } from '@/features/oee/components/dashboard-filters';
import { LinePerformanceChart } from '@/features/oee/components/line-performance-chart';
import { LossDistribution } from '@/features/oee/components/loss-distribution';
import { MetricCard } from '@/features/oee/components/metric-card';
import { OeeGauge } from '@/features/oee/components/oee-gauge';
import { ParetoChart } from '@/features/oee/components/pareto-chart';
import { TrendChart } from '@/features/oee/components/trend-chart';
import type {
    AppliedFilters,
    DashboardFilterOptions,
    OeeReport,
    RankedStop,
} from '@/features/oee/types';
import {
    formatDuration,
    formatMinutes,
    formatNumber,
    formatPercentage,
    lossColor,
} from '@/features/oee/utils';
import { dashboard } from '@/routes/oee';
import * as productions from '@/routes/oee/productions';

type Props = {
    report?: OeeReport;
    ranking?: RankedStop[];
    filterOptions?: DashboardFilterOptions;
    appliedFilters: AppliedFilters;
};

const EMPTY_OPTIONS: DashboardFilterOptions = {
    lineas: [],
    marcas: [],
    years: [],
    componentes: [],
    sorts: [],
};

function ReportSections({ report }: { report: OeeReport }) {
    const { summary } = report;

    const lostMinutes = summary.effectiveMinutes - summary.productiveMinutes;

    return (
        <>
            <div className="grid gap-4 lg:grid-cols-4">
                <Card className="lg:col-span-2">
                    <CardContent className="flex flex-col items-center justify-center gap-6 sm:flex-row">
                        <OeeGauge
                            value={summary.oee}
                            label="OEE"
                            caption="Tiempo productivo sobre tiempo efectivo"
                        />
                        <OeeGauge
                            value={summary.em}
                            label="EM"
                            caption="Disponibilidad de equipo"
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                    <MetricCard
                        label="Volumen (CU)"
                        value={formatNumber(report.volumen)}
                        hint="CU en horas cerradas"
                    />
                    <MetricCard
                        label="Horas cerradas"
                        value={formatNumber(summary.closedHours)}
                        hint={`${formatDuration(summary.scheduledMinutes)} programados`}
                    />
                    <MetricCard
                        label="Tiempo efectivo"
                        value={formatDuration(summary.effectiveMinutes)}
                        hint={`${formatDuration(summary.unscheduledMinutes)} no programados`}
                    />
                    <MetricCard
                        label="Tiempo perdido"
                        value={formatDuration(lostMinutes)}
                        accent={lossColor('EQ')}
                        hint="Suma de todas las familias de pérdida"
                    />
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Evolución diaria</CardTitle>
                    <CardDescription>
                        OEE y disponibilidad de equipo por día del periodo
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <TrendChart
                        points={report.byDay.map((day) => ({
                            label: day.label,
                            oee: day.oee,
                            em: day.em,
                        }))}
                    />
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Desempeño por línea</CardTitle>
                        <CardDescription>
                            Ordenado de mayor a menor OEE
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <LinePerformanceChart lines={report.byLine} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Distribución de pérdidas</CardTitle>
                        <CardDescription>
                            Minutos perdidos y su impacto sobre el tiempo
                            efectivo
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <LossDistribution
                            lossMinutes={summary.lossMinutes}
                            lossImpact={summary.lossImpact}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function RankingSection({ stops }: { stops: RankedStop[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Pareto de paradas</CardTitle>
                <CardDescription>
                    Los códigos que concentran la mayor parte del tiempo perdido
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-6">
                <ParetoChart stops={stops} />

                {stops.length > 0 && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground uppercase">
                                    <th className="py-2 pr-4 font-medium">
                                        Código
                                    </th>
                                    <th className="py-2 pr-4 font-medium">
                                        Descripción
                                    </th>
                                    <th className="py-2 pr-4 font-medium">
                                        Familia
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Minutos
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Frecuencia
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Acumulado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {stops.map((stop) => (
                                    <tr
                                        key={stop.codigo}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-2 pr-4 font-medium">
                                            {stop.codigo}
                                        </td>
                                        <td className="max-w-xs truncate py-2 pr-4 text-muted-foreground">
                                            {stop.descripcion ?? '—'}
                                        </td>
                                        <td className="py-2 pr-4">
                                            <span className="flex items-center gap-2">
                                                <span
                                                    className="size-2.5 rounded-[3px]"
                                                    style={{
                                                        backgroundColor:
                                                            lossColor(
                                                                stop.tipo,
                                                            ),
                                                    }}
                                                />
                                                <span className="text-muted-foreground">
                                                    {stop.tipoLabel}
                                                </span>
                                            </span>
                                        </td>
                                        <td className="py-2 pr-4 text-right tabular-nums">
                                            {formatMinutes(stop.totalMinutos)}
                                        </td>
                                        <td className="py-2 pr-4 text-right tabular-nums">
                                            {formatNumber(stop.totalFrecuencia)}
                                        </td>
                                        <td className="py-2 text-right font-medium tabular-nums">
                                            {formatPercentage(
                                                stop.porcentajeAcumulado,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function OeeDashboard({
    report,
    ranking,
    filterOptions,
    appliedFilters,
}: Props) {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug ?? '';
    const permissions = page.props.teamPermissions;

    return (
        <>
            <Head title="Panel OEE" />

            <h1 className="sr-only">Panel de eficiencia general de equipos</h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Panel OEE"
                        description="Eficiencia, pérdidas y paradas de las líneas de producción"
                    />

                    <div className="flex gap-2">
                        {permissions?.canViewProductions && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={productions.index(teamSlug)}
                                    prefetch
                                >
                                    <ListChecks />
                                    Turnos
                                </Link>
                            </Button>
                        )}
                        {permissions?.canRecordProduction && (
                            <Button asChild>
                                <Link href={productions.create(teamSlug)}>
                                    <ClipboardList />
                                    Registrar turno
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <DashboardFilters
                    teamSlug={teamSlug}
                    options={filterOptions ?? EMPTY_OPTIONS}
                    applied={appliedFilters}
                />

                <Deferred data="report" fallback={<SummarySkeleton />}>
                    {report ? <ReportSections report={report} /> : null}
                </Deferred>

                <Deferred data="ranking" fallback={<ChartSkeleton />}>
                    {ranking ? <RankingSection stops={ranking} /> : null}
                </Deferred>
            </div>
        </>
    );
}

OeeDashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
