import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { dashboard } from '@/routes/oee';
import * as users from '@/routes/oee/admin/users';
import type { SelectOption, TeamAccessUser } from '@/types';

type Props = {
    users: TeamAccessUser[];
    sections: SelectOption[];
    roles: SelectOption[];
};

export default function TeamUsersIndex({
    users: people,
    sections,
    roles,
}: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Usuarios" />

            <h1 className="sr-only">Usuarios del equipo</h1>

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Usuarios"
                        description="Panel OEE viene marcado al dar acceso y se puede quitar. Un Visor solo ve las secciones que marques. Usuarios es solo del administrador."
                    />

                    <Button asChild>
                        <Link href={users.create(teamSlug)}>
                            <Plus />
                            Nuevo usuario
                        </Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-surface">
                    <table className="w-full min-w-[48rem] text-sm">
                        <thead>
                            <tr className="border-b text-left text-xs text-muted-foreground uppercase">
                                <th className="px-4 py-3 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Correo
                                </th>
                                <th className="px-4 py-3 font-medium">Rol</th>
                                {sections.map((section) => (
                                    <th
                                        key={section.value}
                                        className="px-4 py-3 text-center font-medium"
                                    >
                                        {section.label}
                                    </th>
                                ))}
                                <th className="px-4 py-3 text-right font-medium">
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {people.map((person) => (
                                <UserAccessRow
                                    key={person.id}
                                    person={person}
                                    sections={sections}
                                    roles={roles}
                                    teamSlug={teamSlug}
                                />
                            ))}
                        </tbody>
                    </table>

                    {people.length === 0 && (
                        <p className="px-4 py-12 text-center text-muted-foreground">
                            No hay usuarios en este equipo.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}

function UserAccessRow({
    person,
    sections,
    roles,
    teamSlug,
}: {
    person: TeamAccessUser;
    sections: SelectOption[];
    roles: SelectOption[];
    teamSlug: string;
}) {
    const [selected, setSelected] = useState<string[]>(person.sections);

    const toggle = (value: string) => {
        setSelected((current) =>
            current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value],
        );
    };

    return (
        <tr className="border-b last:border-0" data-test="user-access-row">
            <td className="px-4 py-3 font-medium">{person.name}</td>
            <td className="px-4 py-3 text-muted-foreground">{person.email}</td>
            <td className="px-4 py-3">
                {person.canChangeRole ? (
                    <NativeSelect
                        form={`user-access-${person.id}`}
                        name="role"
                        defaultValue={person.role}
                        className="h-8 w-40"
                        aria-label="Rol"
                    >
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </NativeSelect>
                ) : (
                    <Badge variant={person.isMember ? 'outline' : 'secondary'}>
                        {person.roleLabel}
                    </Badge>
                )}
            </td>
            {sections.map((section) => (
                <td key={section.value} className="px-4 py-3 text-center">
                    <input
                        form={`user-access-${person.id}`}
                        type="checkbox"
                        name="sections[]"
                        value={section.value}
                        checked={selected.includes(section.value)}
                        disabled={person.locked}
                        onChange={() => toggle(section.value)}
                        className="size-4 rounded border-input"
                        aria-label={section.label}
                    />
                </td>
            ))}
            <td className="px-4 py-3">
                <div className="flex flex-wrap justify-end gap-2">
                    <Form
                        id={`user-access-${person.id}`}
                        {...users.update.form([teamSlug, person.id])}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing || person.locked}
                            >
                                {person.isMember ? 'Guardar' : 'Dar acceso'}
                            </Button>
                        )}
                    </Form>
                    {person.canDelete && (
                        <Form {...users.destroy.form([teamSlug, person.id])}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    disabled={processing}
                                    data-test="delete-user"
                                    onClick={(event) => {
                                        if (
                                            !confirm(
                                                `¿Eliminar a ${person.name}? Se borra de la tabla de usuarios.`,
                                            )
                                        ) {
                                            event.preventDefault();
                                        }
                                    }}
                                >
                                    <Trash2 />
                                    Eliminar
                                </Button>
                            )}
                        </Form>
                    )}
                </div>
            </td>
        </tr>
    );
}

TeamUsersIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Usuarios',
            href: props.currentTeam ? users.index(props.currentTeam.slug) : '/',
        },
    ],
});
