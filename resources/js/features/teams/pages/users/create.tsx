import { Form, Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/oee';
import * as users from '@/routes/oee/admin/users';
import type { SelectOption } from '@/types';

type Props = {
    sections: SelectOption[];
};

export default function TeamUsersCreate({ sections }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Nuevo usuario" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    variant="small"
                    title="Nuevo usuario"
                    description="Empieza como Operador con Panel OEE. Puedes quitarlo o marcar más secciones. El rol Visor solo ve lo que marques."
                />

                <Form
                    {...users.store.form(teamSlug)}
                    className="max-w-2xl space-y-6 rounded-xl border bg-card p-6 shadow-surface"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        autoFocus
                                        autoComplete="name"
                                        maxLength={255}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Correo</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoComplete="email"
                                        maxLength={255}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Contraseña</Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        autoComplete="new-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar contraseña
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            </div>

                            <fieldset className="grid gap-3">
                                <legend className="text-sm font-medium">
                                    Secciones
                                </legend>
                                <div className="flex flex-wrap gap-4">
                                    {sections.map((section) => (
                                        <label
                                            key={section.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                name="sections[]"
                                                value={section.value}
                                                defaultChecked={
                                                    section.value === 'oee'
                                                }
                                                className="size-4 rounded border-input"
                                            />
                                            {section.label}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.sections} />
                            </fieldset>

                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>
                                    Guardar usuario
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={users.index(teamSlug)}>
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

TeamUsersCreate.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Usuarios',
            href: props.currentTeam ? users.index(props.currentTeam.slug) : '/',
        },
        {
            title: 'Nuevo',
            href: props.currentTeam
                ? users.create(props.currentTeam.slug)
                : '/',
        },
    ],
});
