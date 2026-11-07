import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, ListChecks } from 'lucide-react';
import type { CSSProperties } from 'react';
import { MobileActionBar } from '@/components/mobile-action-bar';
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
} from '@/features/oee/reporting/components/chart-skeleton';
import { DailyVolumeList } from '@/features/oee/reporting/components/daily-volume-list';
import { DashboardFilters } from '@/features/oee/reporting/components/dashboard-filters';
import { LinePerformanceChart } from '@/features/oee/reporting/components/line-performance-chart';
import { LossDistribution } from '@/features/oee/reporting/components/loss-distribution';
import { MetricCard } from '@/features/oee/reporting/components/metric-card';
import { OeeGauge } from '@/features/oee/reporting/components/oee-gauge';
import { OeeStatusBadge } from '@/features/oee/reporting/components/oee-status-badge';
import { ParetoChart } from '@/features/oee/reporting/components/pareto-chart';
import { TrendChart } from '@/features/oee/reporting/components/trend-chart';
import type {
    AppliedFilters,
    DashboardFilterOptions,
    OeeReport,
    RankedStop,
} from '@/features/oee/types';
import {
    formatMinutes,
    formatNumber,
    formatPercentage,
    formatQuantity,
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

/**
 * Position of a section in the entrance sequence; see the `rise-in` utility.
 */
function stagger(index: number): CSSProperties {
    return { '--i': index } as CSSProperties;
}

const EMPTY_OPTIONS: DashboardFilterOptions = {
    lineas: [],
    marcas: [],
    years: [],
    componentes: [],
    sorts: [],
};

function ReportSections({ report }: { report: OeeReport }) {
    const { summary } = report;

    return (
        <>
            <div className="grid items-start gap-4 lg:grid-cols-4">
                <Card
                    className="rise-in border-primary/25 bg-linear-to-br from-primary/15 via-card to-card lg:col-span-2"
                    style={stagger(0)}
                >
                    <CardContent className="flex flex-col items-center justify-center gap-6 sm:flex-row sm:justify-around">
                        <div className="flex flex-col items-center gap-3">
                            <OeeGauge
                                size="hero"
                                value={summary.oee}
                                label="OEE"
                                caption="Tiempo productivo sobre tiempo efectivo"
                            />
                            <OeeStatusBadge value={summary.oee} />
                        </div>
                        <OeeGauge
                            value={summary.em}
                            label="EM"
                            caption="Disponibilidad de equipo"
                        />
                    </CardContent>
                </Card>

                <div
                    className="grid rise-in gap-4 lg:col-span-2"
                    style={stagger(1)}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <MetricCard
                            label="Volumen (CU)"
                            value={formatNumber(report.volumen)}
                            hint="CU en horas cerradas"
                        />
                        <MetricCard
                            label="Parihuelas (PH)"
                            value={formatQuantity(report.parihuelas)}
                            hint="PH acumulados en horas cerradas"
                        />
                    </div>

                    <DailyVolumeList days={report.volumenPorDia} />
                </div>
            </div>

            <Card className="rise-in" style={stagger(2)}>
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
                <Card className="rise-in" style={stagger(3)}>
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

                <Card className="rise-in" style={stagger(4)}>
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
        <Card className="rise-in" style={stagger(0)}>
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
                        <table className="table-stack w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-caption text-muted-foreground uppercase">
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
                                        <td
                                            data-label="Código"
                                            className="py-2 pr-4 font-medium"
                                        >
                                            {stop.codigo}
                                        </td>
                                        <td
                                            data-label="Descripción"
                                            className="max-w-xs truncate py-2 pr-4 text-muted-foreground"
                                        >
                                            {stop.descripcion ?? '—'}
                                        </td>
                                        <td
                                            data-label="Familia"
                                            className="py-2 pr-4"
                                        >
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
                                        <td
                                            data-label="Minutos"
                                            className="py-2 pr-4 text-right tabular-nums"
                                        >
                                            {formatMinutes(stop.totalMinutos)}
                                        </td>
                                        <td
                                            data-label="Frecuencia"
                                            className="py-2 pr-4 text-right tabular-nums"
                                        >
                                            {formatNumber(stop.totalFrecuencia)}
                                        </td>
                                        <td
                                            data-label="Acumulado"
                                            className="py-2 text-right font-medium tabular-nums"
                                        >
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

    const actions = (
        <>
            {permissions?.canViewProductions && (
                <Button variant="outline" className="press" asChild>
                    <Link href={productions.index(teamSlug)} prefetch>
                        <ListChecks />
                        Turnos
                    </Link>
                </Button>
            )}
            {permissions?.canRecordProduction && (
                <Button className="press" asChild>
                    <Link href={productions.create(teamSlug)}>
                        <ClipboardList />
                        Registrar turno
                    </Link>
                </Button>
            )}
        </>
    );

    return (
        <>
            <Head title="Panel OEE" />

            <h1 className="sr-only">Panel de eficiencia general de equipos</h1>

            <div className="mx-auto flex w-full max-w-[90rem] flex-col gap-4 p-4 md:gap-6 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl text-title font-semibold">
                            Panel OEE
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Eficiencia, pérdidas y paradas de las líneas de
                            producción
                        </p>
                    </div>

                    <div className="hidden gap-2 md:flex">{actions}</div>
                </div>

                <div className="rise-in">
                    <DashboardFilters
                        teamSlug={teamSlug}
                        options={filterOptions ?? EMPTY_OPTIONS}
                        applied={appliedFilters}
                    />
                </div>

                <Deferred data="report" fallback={<SummarySkeleton />}>
                    {report ? <ReportSections report={report} /> : null}
                </Deferred>

                <Deferred data="ranking" fallback={<ChartSkeleton />}>
                    {ranking ? <RankingSection stops={ranking} /> : null}
                </Deferred>
            </div>

            {(permissions?.canViewProductions ||
                permissions?.canRecordProduction) && (
                <MobileActionBar>{actions}</MobileActionBar>
            )}
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
