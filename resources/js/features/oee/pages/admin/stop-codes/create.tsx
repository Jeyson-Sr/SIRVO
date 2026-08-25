import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { StopCodeForm } from '@/features/oee/components/stop-code-form';
import type { StopTypeAdminOption } from '@/features/oee/types';
import { dashboard } from '@/routes/oee';
import * as stopCodes from '@/routes/oee/admin/stop-codes';

type Props = {
    types: StopTypeAdminOption[];
};

export default function StopCodesCreate({ types }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Nuevo código de parada" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title="Nuevo código de parada"
                    description="Indica a qué campo del árbol de pérdidas alimenta y si es de Tetra Pak"
                />

                <Form
                    {...stopCodes.store.form(teamSlug)}
                    className="max-w-2xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <>
                            <StopCodeForm types={types} errors={errors} />

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar código
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
            </div>
        </>
    );
}

StopCodesCreate.layout = (props: {
    currentTeam?: { slug: string } | null;
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
            title: 'Nuevo',
            href: props.currentTeam
                ? stopCodes.create(props.currentTeam.slug)
                : '/',
        },
    ],
});
