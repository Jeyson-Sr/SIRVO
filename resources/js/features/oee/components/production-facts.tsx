import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ProductionDetail } from '@/features/oee/types';
import { formatDate, formatNumber } from '@/features/oee/utils';

type FactSheet = { label: string; value: string }[];

function factsOf(production: ProductionDetail): FactSheet {
    return [
        { label: 'Fecha', value: formatDate(production.fecha) },
        { label: 'Turno', value: production.turnoLabel },
        { label: 'Línea', value: production.linea },
        { label: 'Orden de producción', value: production.op },
        { label: 'SKU', value: production.sku ?? '—' },
        { label: 'Marca', value: production.marca ?? '—' },
        { label: 'Sabor', value: production.sabor ?? '—' },
        { label: 'Formato', value: production.formato ?? '—' },
        {
            label: 'Pallets por hora',
            value: formatNumber(production.palletsPorHora),
        },
        { label: 'BPH', value: formatNumber(production.bph) },
        { label: 'Ingeniero', value: production.ingeniero ?? '—' },
        { label: 'Operador', value: production.operador ?? '—' },
    ];
}

export function ProductionFacts({
    production,
}: {
    production: ProductionDetail;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Datos del turno</CardTitle>
            </CardHeader>
            <CardContent>
                <dl className="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
                    {factsOf(production).map((fact) => (
                        <div key={fact.label}>
                            <dt className="text-xs text-muted-foreground uppercase">
                                {fact.label}
                            </dt>
                            <dd className="text-sm font-medium">
                                {fact.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </CardContent>
        </Card>
    );
}
