import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { SkuFilters, SkuListItem } from '@/features/oee/types';
import { formatNumber, formatQuantity } from '@/features/oee/utils';
import { dashboard } from '@/routes/oee';
import * as skus from '@/routes/oee/admin/skus';
import type { Paginated } from '@/types';

type Props = {
    skus: Paginated<SkuListItem>;
    lines: string[];
    filters: SkuFilters;
};

export default function SkusIndex({ skus: catalog, lines, filters }: Props) {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug ?? '';
    const canManage = page.props.teamPermissions?.canManageCatalog ?? false;

    return (
        <>
            <Head title="Productos" />

            <h1 className="sr-only">Catálogo de productos</h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Productos"
                        description="BPH por línea, PH calculado y empaque usados en OEE y volumen"
                    />

                    {canManage && (
                        <Button asChild>
                            <Link href={skus.create(teamSlug)}>
                                <Plus />
                                Nuevo producto
                            </Link>
                        </Button>
                    )}
                </div>

                <Form
                    action={skus.index.url(teamSlug)}
                    method="get"
                    className="grid gap-3 rounded-xl border bg-card p-4 shadow-surface sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div className="grid gap-1.5 lg:col-span-2">
                        <Label htmlFor="search">Buscar</Label>
                        <Input
                            id="search"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="SKU, descripción o marca"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="linea">Línea</Label>
                        <NativeSelect
                            id="linea"
                            name="linea"
                            defaultValue={filters.linea}
                        >
                            <option value="">Todas</option>
                            {lines.map((line) => (
                                <option key={line} value={line}>
                                    {line}
                                </option>
                            ))}
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

                    <div className="flex items-end lg:col-span-4">
                        <Button type="submit" variant="outline">
                            Filtrar
                        </Button>
                    </div>
                </Form>

                <div className="flex flex-col gap-3">
                    {catalog.data.map((sku) => {
                        const row = (
                            <>
                                <div className="min-w-0">
                                    <p className="flex flex-wrap items-center gap-2 font-medium">
                                        <span className="tabular-nums">
                                            {sku.sku}
                                        </span>
                                        <Badge variant="outline">
                                            {sku.linea}
                                        </Badge>
                                        {!sku.activo && (
                                            <Badge variant="secondary">
                                                Inactivo
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {sku.descripcion}
                                        {sku.marca ? ` · ${sku.marca}` : ''}
                                    </p>
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    PH {formatQuantity(sku.palletsPorHora)}
                                    {' · '}
                                    {formatNumber(sku.bph)} BPH
                                    {sku.um ? ` · U.M ${sku.um}` : ''}
                                    {sku.formato ? ` · ${sku.formato} L` : ''}
                                    {sku.mercado ? ` · ${sku.mercado}` : ''}
                                </p>
                            </>
                        );

                        const rowClassName =
                            'flex flex-wrap items-center justify-between gap-4 rounded-lg border p-4';

                        return canManage ? (
                            <Link
                                key={sku.id}
                                href={skus.edit([teamSlug, sku.id])}
                                data-test="sku-row"
                                className={`${rowClassName} transition-colors hover:border-primary/40 hover:bg-accent/40`}
                            >
                                {row}
                            </Link>
                        ) : (
                            <div
                                key={sku.id}
                                data-test="sku-row"
                                className={rowClassName}
                            >
                                {row}
                            </div>
                        );
                    })}

                    {catalog.data.length === 0 && (
                        <p className="py-12 text-center text-muted-foreground">
                            No hay productos con esos filtros.
                        </p>
                    )}
                </div>

                {catalog.last_page > 1 && (
                    <nav
                        className="flex flex-wrap items-center justify-center gap-1"
                        aria-label="Paginación"
                    >
                        {catalog.links.map((link, index) =>
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

SkusIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Productos',
            href: props.currentTeam ? skus.index(props.currentTeam.slug) : '/',
        },
    ],
});
