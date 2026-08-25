import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, Gauge, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ProductionListItem } from '@/features/oee/types';
import { formatDate } from '@/features/oee/utils';
import { dashboard } from '@/routes/oee';
import * as productions from '@/routes/oee/productions';
import type { Paginated } from '@/types';

type Props = {
    productions: Paginated<ProductionListItem>;
};

export default function ProductionsIndex({ productions: list }: Props) {
    const { props } = usePage();
    const teamSlug = props.currentTeam?.slug ?? '';
    const permissions = props.teamPermissions;

    return (
        <>
            <Head title="Turnos registrados" />

            <h1 className="sr-only">Turnos de producción registrados</h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Turnos registrados"
                        description="Producción capturada por línea, fecha y turno"
                    />

                    <div className="flex gap-2">
                        {permissions?.canViewOee && (
                            <Button variant="outline" asChild>
                                <Link href={dashboard(teamSlug)} prefetch>
                                    <Gauge />
                                    Panel OEE
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

                <div className="flex flex-col gap-3">
                    {list.data.map((production) => (
                        <div
                            key={production.id}
                            data-test="production-row"
                            className="flex flex-wrap items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <Link
                                href={productions.show([
                                    teamSlug,
                                    production.id,
                                ])}
                                className="min-w-0 flex-1 rounded-md transition-colors hover:text-primary"
                            >
                                <p className="flex items-center gap-2 font-medium">
                                    <span>{production.linea}</span>
                                    <span className="text-muted-foreground">
                                        ·
                                    </span>
                                    <span className="tabular-nums">
                                        OP {production.op}
                                    </span>
                                    {production.isClosed ? (
                                        <Badge variant="secondary">
                                            Cerrado
                                        </Badge>
                                    ) : (
                                        <Badge>Abierto</Badge>
                                    )}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {formatDate(production.fecha)} · Turno{' '}
                                    {production.turnoLabel}
                                    {production.marca
                                        ? ` · ${production.marca}`
                                        : ''}
                                    {production.descripcion
                                        ? ` · ${production.descripcion}`
                                        : ''}
                                </p>
                            </Link>

                            <div className="flex flex-wrap items-center gap-3">
                                <div className="text-right text-sm">
                                    <p className="font-medium tabular-nums">
                                        {production.closedHours} /{' '}
                                        {production.hours}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        horas cerradas
                                    </p>
                                </div>

                                {production.canEdit && (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={productions.edit([
                                                teamSlug,
                                                production.id,
                                            ])}
                                            data-test="edit-production"
                                        >
                                            <Pencil />
                                            Editar
                                        </Link>
                                    </Button>
                                )}

                                {production.canDelete && (
                                    <Form
                                        {...productions.destroy.form([
                                            teamSlug,
                                            production.id,
                                        ])}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                size="sm"
                                                disabled={processing}
                                                data-test="delete-production"
                                            >
                                                <Trash2 />
                                                Eliminar
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    ))}

                    {list.data.length === 0 && (
                        <p className="py-12 text-center text-muted-foreground">
                            Todavía no hay turnos registrados para este equipo.
                        </p>
                    )}
                </div>

                {list.last_page > 1 && (
                    <nav
                        className="flex flex-wrap items-center justify-center gap-1"
                        aria-label="Paginación"
                    >
                        {list.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <span
                                    key={index}
                                    className="px-2 text-sm text-muted-foreground"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

ProductionsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
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
