import { Deferred, Head, usePage } from '@inertiajs/react';
import { ChevronDown, CircleCheck } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { ChartSkeleton } from '@/features/oee/reporting/components/chart-skeleton';
import { DashboardFilters } from '@/features/oee/reporting/components/dashboard-filters';
import type {
    AppliedFilters,
    DashboardFilterOptions,
    ExplainedStop,
    StopOccurrence,
} from '@/features/oee/types';
import {
    formatMinutes,
    formatNumber,
    formatPercentage,
    lossColor,
} from '@/features/oee/utils';
import { paradas } from '@/routes/oee';

type Props = {
    codes?: ExplainedStop[];
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

export default function StopComments({
    codes,
    filterOptions,
    appliedFilters,
}: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Paradas" />

            <h1 className="sr-only">Paradas por línea y producto</h1>

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title="Paradas"
                    description="Minutos y frecuencia de cada código, agrupados por línea y producto"
                />

                <DashboardFilters
                    teamSlug={teamSlug}
                    options={filterOptions ?? EMPTY_OPTIONS}
                    applied={appliedFilters}
                    action={paradas.form(teamSlug)}
                    clearHref={paradas.url(teamSlug)}
                />

                <Deferred data="codes" fallback={<ChartSkeleton />}>
                    {codes ? <StopReport codes={codes} /> : null}
                </Deferred>
            </div>
        </>
    );
}

StopComments.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Paradas',
            href: props.currentTeam ? paradas(props.currentTeam.slug) : '/',
        },
    ],
});

function StopReport({ codes }: { codes: ExplainedStop[] }) {
    if (codes.length === 0) {
        return (
            <EmptyState
                icon={CircleCheck}
                title="Sin paradas en el periodo"
                description="No hay paradas registradas con estos filtros."
            />
        );
    }

    return (
        <div className="flex flex-col gap-6">
            <RankingTable codes={codes} />

            {codes.map((code) => (
                <CodeCard key={code.codigo} code={code} />
            ))}
        </div>
    );
}

function RankingTable({ codes }: { codes: ExplainedStop[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Top de códigos</CardTitle>
                <CardDescription>
                    Minutos perdidos y frecuencia de cada código
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto rounded-md border">
                    <table className="table-stack w-full text-sm md:min-w-[36rem]">
                        <thead>
                            <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                <th className="px-3 py-2 font-medium">
                                    Código
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Catálogo
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    Min
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    Frecuencia
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    Acumulado
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {codes.map((code) => (
                                <tr
                                    key={code.codigo}
                                    className="border-b last:border-0"
                                >
                                    <td
                                        data-label="Código"
                                        className="px-3 py-2"
                                    >
                                        <CodeBadge
                                            codigo={code.codigo}
                                            tipo={code.tipo}
                                        />
                                    </td>
                                    <td
                                        data-label="Catálogo"
                                        className="max-w-64 px-3 py-2 text-muted-foreground"
                                    >
                                        {code.descripcion ?? '—'}
                                    </td>
                                    <td
                                        data-label="Min"
                                        className="px-3 py-2 text-right tabular-nums"
                                    >
                                        {formatMinutes(code.totalMinutos)}
                                    </td>
                                    <td
                                        data-label="Frecuencia"
                                        className="px-3 py-2 text-right tabular-nums"
                                    >
                                        {formatNumber(code.totalFrecuencia)}
                                    </td>
                                    <td
                                        data-label="Acumulado"
                                        className="px-3 py-2 text-right font-medium tabular-nums"
                                    >
                                        {formatPercentage(
                                            code.porcentajeAcumulado,
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

function CodeCard({ code }: { code: ExplainedStop }) {
    return (
        <Card data-test="stop-comment-card">
            <Collapsible>
                <CardHeader>
                    <CollapsibleTrigger
                        className="group flex w-full cursor-pointer items-center justify-between gap-3 text-left"
                        data-test="stop-comment-toggle"
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            <ChevronDown className="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                            <CodeBadge codigo={code.codigo} tipo={code.tipo} />
                            <span className="truncate text-base font-medium">
                                {code.descripcion ?? code.tipoLabel}
                            </span>
                        </span>
                        <span className="shrink-0 text-sm font-normal text-muted-foreground">
                            {formatMinutes(code.totalMinutos)} ·{' '}
                            {formatNumber(code.totalFrecuencia)} veces
                        </span>
                    </CollapsibleTrigger>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent>
                        <OccurrenceTable occurrences={code.ocurrencias} />
                    </CardContent>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
}

function OccurrenceTable({ occurrences }: { occurrences: StopOccurrence[] }) {
    if (occurrences.length === 0) {
        return <p className="text-sm text-muted-foreground">Sin ocurrencias</p>;
    }

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="table-stack w-full text-sm">
                <thead>
                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                        <th className="px-3 py-2 font-medium">Línea</th>
                        <th className="px-3 py-2 font-medium">Producto</th>
                        <th className="px-3 py-2 text-right font-medium">
                            Min
                        </th>
                        <th className="px-3 py-2 text-right font-medium">
                            Veces
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {occurrences.map((occurrence) => (
                        <tr
                            key={`${occurrence.linea}|${occurrence.sku}`}
                            className="border-b last:border-0"
                        >
                            <td
                                data-label="Línea"
                                className="px-3 py-2 whitespace-nowrap"
                            >
                                {occurrence.linea}
                            </td>
                            <td data-label="Producto" className="px-3 py-2">
                                <span className="block">
                                    {occurrence.producto}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {occurrence.sku}
                                </span>
                            </td>
                            <td
                                data-label="Min"
                                className="px-3 py-2 text-right tabular-nums"
                            >
                                {formatMinutes(occurrence.totalMinutos)}
                            </td>
                            <td
                                data-label="Veces"
                                className="px-3 py-2 text-right tabular-nums"
                            >
                                {formatNumber(occurrence.totalFrecuencia)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function CodeBadge({
    codigo,
    tipo,
}: {
    codigo: string;
    tipo: ExplainedStop['tipo'];
}) {
    return (
        <span className="flex items-center gap-2">
            <span
                className="size-2.5 shrink-0 rounded-[3px]"
                style={{ backgroundColor: lossColor(tipo) }}
            />
            <span className="font-medium">{codigo}</span>
        </span>
    );
}
