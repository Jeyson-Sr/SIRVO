import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { SkuForm } from '@/features/oee/components/sku-form';
import type { SkuBphChange, SkuFormValues } from '@/features/oee/types';
import { formatNumber } from '@/features/oee/utils';
import { dashboard } from '@/routes/oee';
import * as skus from '@/routes/oee/admin/skus';

type Props = {
    sku: SkuFormValues;
    bphChanges: SkuBphChange[];
    lines: string[];
    inUse: boolean;
};

export default function SkusEdit({
    sku,
    bphChanges,
    lines,
    inUse,
}: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editar ${sku.sku}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title={`Editar ${sku.sku}`}
                    description="El PH y el paq. pallet se calculan con BPH, U.M, paq. cama y nivel. El BPH queda registrado por línea."
                />

                <Form
                    {...skus.update.form([teamSlug, sku.id])}
                    className="max-w-3xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <>
                            <SkuForm
                                lines={lines}
                                values={sku}
                                errors={errors}
                            />

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={skus.index(teamSlug)}>
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <section className="max-w-3xl space-y-3 rounded-xl border bg-card p-6 shadow-surface">
                    <div>
                        <h2 className="font-medium">Cambios de BPH</h2>
                        <p className="text-sm text-muted-foreground">
                            Historial del BPH de este SKU y la línea en la que
                            se registró.
                        </p>
                    </div>

                    {bphChanges.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground uppercase">
                                        <th className="py-2 pr-3 font-medium">
                                            Fecha
                                        </th>
                                        <th className="py-2 pr-3 font-medium">
                                            Línea
                                        </th>
                                        <th className="py-2 pr-3 text-right font-medium">
                                            BPH anterior
                                        </th>
                                        <th className="py-2 pr-3 text-right font-medium">
                                            BPH nuevo
                                        </th>
                                        <th className="py-2 font-medium">
                                            Usuario
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {bphChanges.map((change) => (
                                        <tr
                                            key={change.id}
                                            className="border-b last:border-0"
                                            data-test="bph-change-row"
                                        >
                                            <td className="py-2 pr-3 tabular-nums">
                                                {change.createdAt}
                                            </td>
                                            <td className="py-2 pr-3">
                                                {change.linea}
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">
                                                {change.bphAnterior === null
                                                    ? '—'
                                                    : formatNumber(
                                                          change.bphAnterior,
                                                      )}
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">
                                                {formatNumber(change.bphNuevo)}
                                            </td>
                                            <td className="py-2 text-muted-foreground">
                                                {change.userName ?? 'Sistema'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Aún no hay cambios de BPH para este producto.
                        </p>
                    )}
                </section>

                <Form
                    {...skus.destroy.form([teamSlug, sku.id])}
                    className="max-w-3xl rounded-xl border border-destructive/30 bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="font-medium">Eliminar producto</p>
                                <p className="text-sm text-muted-foreground">
                                    {inUse
                                        ? 'Ya se usó en un turno. Desactívalo arriba en lugar de borrarlo.'
                                        : 'Se quita del catálogo. Esta acción no se puede deshacer.'}
                                </p>
                                {errors.sku && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.sku}
                                    </p>
                                )}
                            </div>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing || inUse}
                            >
                                Eliminar
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

SkusEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    sku?: { sku: string; id: number };
}) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Productos',
            href: props.currentTeam ? skus.index(props.currentTeam.slug) : '/',
        },
        {
            title: props.sku?.sku ?? 'Editar',
            href:
                props.currentTeam && props.sku
                    ? skus.edit([props.currentTeam.slug, props.sku.id])
                    : '/',
        },
    ],
});
