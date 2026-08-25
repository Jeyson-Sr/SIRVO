import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Gauge, Lock, LockOpen, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ProductionFacts } from '@/features/oee/components/production-facts';
import { ProductionHourCard } from '@/features/oee/components/production-hour-card';
import type { ProductionDetail } from '@/features/oee/types';
import { formatDate } from '@/features/oee/utils';
import { dashboard } from '@/routes/oee';
import * as productions from '@/routes/oee/productions';

type Props = {
    production: ProductionDetail;
    canEdit?: boolean;
    canDelete?: boolean;
};

export default function ProductionShow({
    production,
    canEdit = false,
    canDelete = false,
}: Props) {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug ?? '';
    const permissions = page.props.teamPermissions;
    const routeArgs = [teamSlug, production.id] as [string, number];

    // Closing a shift with open hours is refused server-side.
    const closeError = page.props.errors?.production;
    const openHours = production.hours.filter((hour) => !hour.closed).length;

    return (
        <>
            <Head title={`Turno ${production.linea} · ${production.op}`} />

            <h1 className="sr-only">
                Turno {production.turnoLabel} de la línea {production.linea}
            </h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title={`${production.linea} · OP ${production.op}`}
                        description={`${formatDate(production.fecha)} · Turno ${production.turnoLabel}${
                            production.createdBy
                                ? ` · Registrado por ${production.createdBy}`
                                : ''
                        }`}
                    />

                    <div className="flex flex-wrap items-center gap-2">
                        {permissions?.canViewOee && (
                            <Button variant="outline" asChild>
                                <Link href={dashboard(teamSlug)} prefetch>
                                    <Gauge />
                                    Panel OEE
                                </Link>
                            </Button>
                        )}

                        {canEdit && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={productions.edit(routeArgs)}
                                    data-test="edit-production"
                                >
                                    <Pencil />
                                    Editar
                                </Link>
                            </Button>
                        )}

                        {canDelete && (
                            <Form {...productions.destroy.form(routeArgs)}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                        data-test="delete-production"
                                    >
                                        <Trash2 />
                                        Eliminar
                                    </Button>
                                )}
                            </Form>
                        )}

                        {production.isClosed ? (
                            <Form
                                {...productions.reopen.form(routeArgs)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                        data-test="reopen-production"
                                    >
                                        <LockOpen />
                                        Reabrir turno
                                    </Button>
                                )}
                            </Form>
                        ) : (
                            <Form
                                {...productions.close.form(routeArgs)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="close-production"
                                    >
                                        <Lock />
                                        Cerrar turno
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </div>

                {closeError && (
                    <p className="rounded-lg border border-destructive px-4 py-3 text-sm text-destructive">
                        {closeError}
                    </p>
                )}

                {production.isClosed ? (
                    <p className="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
                        Este turno está cerrado. Sus cifras ya cuentan como
                        registro histórico y no admiten cambios.
                    </p>
                ) : openHours > 0 ? (
                    <p className="rounded-lg border border-status-warning/40 bg-status-warning/5 px-4 py-3 text-sm">
                        Quedan {openHours} horas sin cerrar. El turno no puede
                        cerrarse hasta completarlas.
                    </p>
                ) : null}

                <ProductionFacts production={production} />

                <Card>
                    <CardHeader>
                        <CardTitle>Horas del turno</CardTitle>
                        <CardDescription>
                            Meta, producción real y paradas registradas en cada
                            hora
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {production.hours.map((hour, index) => (
                            <ProductionHourCard
                                key={hour.id}
                                hour={hour}
                                acumulado={production.hours
                                    .slice(0, index + 1)
                                    .reduce(
                                        (total, slot) =>
                                            total + (slot.producido ?? 0),
                                        0,
                                    )}
                            />
                        ))}

                        {production.hours.length === 0 && (
                            <p className="py-8 text-center text-muted-foreground">
                                Este turno no tiene horas registradas.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ProductionShow.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Turnos',
            href: props.currentTeam
                ? productions.index(props.currentTeam.slug)
                : '/',
        },
    ],
});
