import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { StopCodeForm } from '@/features/oee/components/stop-code-form';
import type {
    StopCodeFormValues,
    StopTypeAdminOption,
} from '@/features/oee/types';
import { dashboard } from '@/routes/oee';
import * as stopCodes from '@/routes/oee/admin/stop-codes';

type Props = {
    code: StopCodeFormValues;
    types: StopTypeAdminOption[];
    inUse: boolean;
};

export default function StopCodesEdit({ code, types, inUse }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editar ${code.codigo}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title={`Editar ${code.codigo}`}
                    description="Cambia el árbol de pérdidas, el detalle o si aplica a Tetra Pak"
                />

                <Form
                    {...stopCodes.update.form([teamSlug, code.id])}
                    className="max-w-2xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <>
                            <StopCodeForm
                                types={types}
                                values={code}
                                errors={errors}
                            />

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar cambios
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={stopCodes.index(teamSlug)}>
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <Form
                    {...stopCodes.destroy.form([teamSlug, code.id])}
                    className="max-w-2xl rounded-xl border border-destructive/30 bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="font-medium">Eliminar código</p>
                                <p className="text-sm text-muted-foreground">
                                    {inUse
                                        ? 'Ya se usó en un turno. Desactívalo arriba en lugar de borrarlo.'
                                        : 'Se quita del catálogo. Esta acción no se puede deshacer.'}
                                </p>
                                {errors.codigo && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.codigo}
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

StopCodesEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    code?: { codigo: string; id: number };
}) => ({
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
        {
            title: props.code?.codigo ?? 'Editar',
            href:
                props.currentTeam && props.code
                    ? stopCodes.edit([props.currentTeam.slug, props.code.id])
                    : '/',
        },
    ],
});
