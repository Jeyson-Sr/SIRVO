import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type {
    StopCodeFilters,
    StopCodeListItem,
    StopTypeAdminOption,
} from '@/features/oee/types';
import { dashboard } from '@/routes/oee';
import * as stopCodes from '@/routes/oee/admin/stop-codes';
import type { Paginated } from '@/types';

type Props = {
    codes: Paginated<StopCodeListItem>;
    types: StopTypeAdminOption[];
    filters: StopCodeFilters;
};

export default function StopCodesIndex({ codes, types, filters }: Props) {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug ?? '';
    const canManage = page.props.teamPermissions?.canManageCatalog ?? false;

    return (
        <>
            <Head title="Códigos de parada" />

            <h1 className="sr-only">Catálogo de códigos de parada</h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Códigos de parada"
                        description="Árbol de pérdidas (OPD, RD, QD…) y si el código es de Tetra Pak"
                    />

                    {canManage && (
                        <Button asChild>
                            <Link href={stopCodes.create(teamSlug)}>
                                <Plus />
                                Nuevo código
                            </Link>
                        </Button>
                    )}
                </div>

                <Form
                    action={stopCodes.index.url(teamSlug)}
                    method="get"
                    className="grid gap-3 rounded-xl border bg-card p-4 shadow-surface sm:grid-cols-2 lg:grid-cols-5"
                >
                    <div className="grid gap-1.5 lg:col-span-2">
                        <Label htmlFor="search">Buscar</Label>
                        <Input
                            id="search"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Código o detalle"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="tipo_parada">Árbol</Label>
                        <NativeSelect
                            id="tipo_parada"
                            name="tipo_parada"
                            defaultValue={filters.tipo_parada}
                        >
                            <option value="">Todos</option>
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="es_tetra_pak">Tetra Pak</Label>
                        <NativeSelect
                            id="es_tetra_pak"
                            name="es_tetra_pak"
                            defaultValue={filters.es_tetra_pak}
                        >
                            <option value="">Todos</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </NativeSelect>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="activo">Estado</Label>
                        <NativeSelect
                            id="activo"
                            name="activo"
                            defaultValue={filters.activo}
                        >
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </NativeSelect>
                    </div>

                    <div className="flex items-end lg:col-span-5">
                        <Button type="submit" variant="outline">
                            Filtrar
                        </Button>
                    </div>
                </Form>

                <div className="flex flex-col gap-3">
                    {codes.data.map((code) => {
                        const row = (
                            <>
                                <div className="min-w-0">
                                    <p className="flex flex-wrap items-center gap-2 font-medium">
                                        <span className="tabular-nums">
                                            {code.codigo}
                                        </span>
                                        <Badge variant="outline">
                                            {code.tipo}
                                        </Badge>
                                        {code.esTetraPak && (
                                            <Badge>Tetra Pak</Badge>
                                        )}
                                        {!code.activo && (
                                            <Badge variant="secondary">
                                                Inactivo
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {code.detalle}
                                        {code.categoria
                                            ? ` · ${code.categoria}`
                                            : ''}
                                    </p>
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    {code.tipoLabel}
                                </p>
                            </>
                        );

                        const rowClassName =
                            'flex flex-wrap items-center justify-between gap-4 rounded-lg border p-4';

                        return canManage ? (
                            <Link
                                key={code.id}
                                href={stopCodes.edit([teamSlug, code.id])}
                                data-test="stop-code-row"
                                className={`${rowClassName} transition-colors hover:border-primary/40 hover:bg-accent/40`}
                            >
                                {row}
                            </Link>
                        ) : (
                            <div
                                key={code.id}
                                data-test="stop-code-row"
                                className={rowClassName}
                            >
                                {row}
                            </div>
                        );
                    })}

                    {codes.data.length === 0 && (
                        <p className="py-12 text-center text-muted-foreground">
                            No hay códigos con esos filtros.
                        </p>
                    )}
                </div>

                {codes.last_page > 1 && (
                    <nav
                        className="flex flex-wrap items-center justify-center gap-1"
                        aria-label="Paginación"
                    >
                        {codes.links.map((link, index) =>
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

StopCodesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Códigos de parada',
            href: props.currentTeam
                ? stopCodes.index(props.currentTeam.slug)
                : '/',
        },
    ],
});
