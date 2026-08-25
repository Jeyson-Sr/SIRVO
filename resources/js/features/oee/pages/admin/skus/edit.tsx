import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { SkuForm } from '@/features/oee/components/sku-form';
import type { SkuFormValues } from '@/features/oee/types';
import { dashboard } from '@/routes/oee';
import * as skus from '@/routes/oee/admin/skus';

type Props = {
    sku: SkuFormValues;
    lines: string[];
    inUse: boolean;
};

export default function SkusEdit({ sku, lines, inUse }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editar ${sku.sku}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title={`Editar ${sku.sku}`}
                    description="Cambia la línea, el PH, el BPH o el contenido usados en OEE y volumen"
                />

                <Form
                    {...skus.update.form([teamSlug, sku.id])}
                    className="max-w-2xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
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

                <Form
                    {...skus.destroy.form([teamSlug, sku.id])}
                    className="max-w-2xl rounded-xl border border-destructive/30 bg-card p-6 shadow-surface"
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
