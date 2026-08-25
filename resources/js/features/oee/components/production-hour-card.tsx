import { Badge } from '@/components/ui/badge';
import { HourStatusBadge } from '@/features/oee/components/hour-status-badge';
import type { ProductionHour } from '@/features/oee/types';
import {
    formatMinutes,
    formatNumber,
    formatQuantity,
    lossColor,
} from '@/features/oee/utils';

type Props = {
    hour: ProductionHour;
    acumulado: number;
};

export function ProductionHourCard({ hour, acumulado }: Props) {
    const comments = [
        { label: 'MNF', text: hour.comments.mnf },
        { label: 'Mantenimiento', text: hour.comments.mantto },
        { label: 'Calidad', text: hour.comments.calidad },
    ].filter((comment) => comment.text);

    return (
        <div className="rounded-xl border bg-card p-4 shadow-tile">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="font-medium tabular-nums">
                        {hour.hourRange}
                    </span>
                    {hour.sku ? (
                        <span className="text-sm text-muted-foreground">
                            SKU {hour.sku}
                        </span>
                    ) : null}
                    {hour.durationMinutes !== 60 ? (
                        <span className="text-sm text-muted-foreground">
                            {formatMinutes(hour.durationMinutes)}
                        </span>
                    ) : null}
                    <HourStatusBadge
                        status={hour.status}
                        label={hour.statusLabel}
                    />
                    {hour.closed ? (
                        <Badge variant="secondary">Cerrada</Badge>
                    ) : null}
                </div>

                <div className="flex gap-6 text-sm tabular-nums">
                    <span>
                        <span className="text-muted-foreground">Meta </span>
                        {formatNumber(hour.estimado)}
                    </span>
                    <span>
                        <span className="text-muted-foreground">Acum. </span>
                        {acumulado > 0 ? formatQuantity(acumulado) : '—'}
                    </span>
                    <span>
                        <span className="text-muted-foreground">
                            Producido{' '}
                        </span>
                        {hour.producido === null
                            ? '—'
                            : formatNumber(hour.producido)}
                    </span>
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-muted-foreground">
                <span>
                    Por justificar:{' '}
                    <span className="tabular-nums">
                        {formatMinutes(hour.minutosAJustificar)}
                    </span>
                </span>
                <span>
                    Justificados:{' '}
                    <span className="tabular-nums">
                        {formatMinutes(hour.minutosJustificados)}
                    </span>
                </span>
                <span
                    className={
                        hour.minutosPendientes > 0
                            ? 'font-medium text-status-warning'
                            : undefined
                    }
                >
                    Pendientes:{' '}
                    <span className="tabular-nums">
                        {formatMinutes(hour.minutosPendientes)}
                    </span>
                </span>
            </div>

            {hour.stops.length > 0 && (
                <div className="mt-3 overflow-x-auto border-t pt-3">
                    <table className="w-full min-w-[28rem] text-sm">
                        <thead>
                            <tr className="text-left text-xs text-muted-foreground">
                                <th className="pb-2 font-medium">Código</th>
                                <th className="pb-2 font-medium">Catálogo</th>
                                <th className="pb-2 font-medium">Min</th>
                                <th className="pb-2 font-medium">Por qué</th>
                            </tr>
                        </thead>
                        <tbody>
                            {hour.stops.map((stop) => (
                                <tr key={stop.id} className="align-top">
                                    <td className="py-1.5 pr-3">
                                        <span className="flex items-center gap-2">
                                            <span
                                                className="size-2.5 shrink-0 rounded-[3px]"
                                                style={{
                                                    backgroundColor: lossColor(
                                                        stop.tipo,
                                                    ),
                                                }}
                                            />
                                            <span className="font-medium">
                                                {stop.codigo}
                                            </span>
                                            {stop.continua && (
                                                <Badge variant="secondary">
                                                    Continúa
                                                </Badge>
                                            )}
                                        </span>
                                    </td>
                                    <td className="py-1.5 pr-3 text-muted-foreground">
                                        {stop.descripcion ?? stop.tipoLabel}
                                    </td>
                                    <td className="py-1.5 pr-3 text-muted-foreground tabular-nums">
                                        {formatMinutes(stop.tiempoMinutos)}
                                    </td>
                                    <td className="py-1.5 text-muted-foreground">
                                        {stop.comentario || '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {comments.length > 0 && (
                <dl className="mt-3 grid gap-1 border-t pt-3 text-xs">
                    {comments.map((comment) => (
                        <div key={comment.label} className="flex gap-2">
                            <dt className="shrink-0 font-medium text-muted-foreground">
                                {comment.label}:
                            </dt>
                            <dd className="min-w-0">{comment.text}</dd>
                        </div>
                    ))}
                </dl>
            )}
        </div>
    );
}
