import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { SkuForm } from '@/features/oee/components/sku-form';
import { dashboard } from '@/routes/oee';
import * as skus from '@/routes/oee/admin/skus';

type Props = {
    lines: string[];
};

export default function SkusCreate({ lines }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Nuevo producto" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title="Nuevo producto"
                    description="Indica la línea, el BPH y el empaque. El PH y el paq. pallet se calculan solos."
                />

                <Form
                    {...skus.store.form(teamSlug)}
                    className="max-w-3xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <>
                            <SkuForm lines={lines} errors={errors} />

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar producto
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
            </div>
        </>
    );
}

SkusCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
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
            title: 'Nuevo',
            href: props.currentTeam ? skus.create(props.currentTeam.slug) : '/',
        },
    ],
});
