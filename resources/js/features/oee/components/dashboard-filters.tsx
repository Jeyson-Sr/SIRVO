import { Form, Link } from '@inertiajs/react';
import { RotateCcw, SlidersHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type {
    AppliedFilters,
    DashboardFilterOptions,
    SelectOption,
} from '@/features/oee/types';
import { dashboard } from '@/routes/oee';

type Props = {
    teamSlug: string;
    options: DashboardFilterOptions;
    applied: AppliedFilters;
    action?: { action: string; method: string };
    clearHref?: string;
};

type FilterSelectProps = {
    name: string;
    label: string;
    defaultValue: string;
    options: SelectOption[];
    placeholder?: string;
};

function FilterSelect({
    name,
    label,
    defaultValue,
    options,
    placeholder,
}: FilterSelectProps) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={name}>{label}</Label>
            <NativeSelect id={name} name={name} defaultValue={defaultValue}>
                {placeholder && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </NativeSelect>
        </div>
    );
}

/** Turn plain strings into the option shape the select expects. */
function toOptions(values: string[]): SelectOption[] {
    return values.map((value) => ({ value, label: value }));
}

/**
 * A plain GET form so any filtered view stays shareable and bookmarkable.
 */
export function DashboardFilters({
    teamSlug,
    options,
    applied,
    action,
    clearHref,
}: Props) {
    const form = action ?? dashboard.form(teamSlug);

    return (
        <Card>
            <CardContent>
                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    className="grid gap-4 md:grid-cols-2 lg:grid-cols-6"
                >
                    {({ processing }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="from">Desde</Label>
                                <Input
                                    id="from"
                                    name="from"
                                    type="date"
                                    defaultValue={applied.from ?? ''}
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="to">Hasta</Label>
                                <Input
                                    id="to"
                                    name="to"
                                    type="date"
                                    defaultValue={applied.to ?? ''}
                                />
                            </div>

                            <FilterSelect
                                name="linea"
                                label="Línea"
                                placeholder="Todas"
                                defaultValue={applied.linea ?? ''}
                                options={toOptions(options.lineas)}
                            />

                            <FilterSelect
                                name="marca"
                                label="Marca"
                                placeholder="Todas"
                                defaultValue={applied.marca ?? ''}
                                options={toOptions(options.marcas)}
                            />

                            <FilterSelect
                                name="componente"
                                label="Componente"
                                placeholder="Todos"
                                defaultValue={applied.componente ?? ''}
                                options={options.componentes}
                            />

                            <FilterSelect
                                name="sort_by"
                                label="Ordenar paradas por"
                                defaultValue={applied.sortBy}
                                options={options.sorts}
                            />

                            <div className="flex items-end gap-2 md:col-span-2 lg:col-span-6">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test="oee-apply-filters"
                                >
                                    <SlidersHorizontal />
                                    {processing ? 'Aplicando…' : 'Aplicar'}
                                </Button>

                                <Button type="button" variant="ghost" asChild>
                                    <Link href={clearHref ?? dashboard(teamSlug)}>
                                        <RotateCcw />
                                        Limpiar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
