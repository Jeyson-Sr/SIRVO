import { ChevronDown, Lock } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { HourStatusBadge } from '@/features/oee/components/hour-status-badge';
import { HourStopsEditor } from '@/features/oee/components/hour-stops-editor';
import type { HourDraft, StopDraft } from '@/features/oee/types';
import {
    accountHour,
    BALANCE_TOLERANCE_MINUTES,
    formatMinutes,
    formatQuantity,
    isHourBalanced,
    STATUS_LABELS,
    statusOf,
} from '@/features/oee/utils';
import { cn } from '@/lib/utils';

/** Server messages for the fields of one hour, keyed as Laravel returns them. */
export type HourErrors = {
    estimado?: string;
    producido?: string;
    stops?: string;
};

type Props = {
    hour: HourDraft;
    acumulado?: number;
    locked?: boolean;
    lockReason?: string;
    canChangeProduct?: boolean;
    onChangeProduct?: () => void;
    onChange: (hour: HourDraft) => void;
    onAddStop: () => void;
    errors?: HourErrors;
};

/**
 * One hour of the shift: its target, its output and the stops that explain
 * whatever it did not produce.
 */
export function HourEntryRow({
    hour,
    acumulado = 0,
    locked = false,
    lockReason = 'Por favor cuadra los tiempos de la hora anterior.',
    canChangeProduct = false,
    onChangeProduct,
    onChange,
    onAddStop,
    errors = {},
}: Props) {
    const [showDetail, setShowDetail] = useState(false);
    const expanded = !locked && showDetail;

    const {
        estimated,
        produced,
        duration,
        toJustify,
        justified,
        pending,
        overBooked,
    } = accountHour(hour);
    const balanced = isHourBalanced(hour);
    const hasError = Object.values(errors).some(Boolean);

    const target = Number.parseFloat(hour.estimado);
    const maxProduced =
        Number.isFinite(target) && target >= 0 ? target : undefined;

    const update = (changes: Partial<HourDraft>) => {
        const next = { ...hour, ...changes };

        onChange({ ...next, closed: isHourBalanced(next) });
    };

    const updateProduced = (value: string) => {
        if (value === '') {
            update({ producido: '' });

            return;
        }

        const produced = Number.parseFloat(value);

        if (
            maxProduced !== undefined &&
            Number.isFinite(produced) &&
            produced > maxProduced
        ) {
            update({ producido: String(maxProduced) });

            return;
        }

        update({ producido: value });
    };

    const removeStop = (clientUuid: string) => {
        update({
            stops: hour.stops.filter((stop) => stop.client_uuid !== clientUuid),
        });
    };

    const updateStop = (clientUuid: string, changes: Partial<StopDraft>) => {
        update({
            stops: hour.stops.map((stop) =>
                stop.client_uuid === clientUuid
                    ? { ...stop, ...changes }
                    : stop,
            ),
        });
    };

    const updateComment = (
        field: keyof HourDraft['comments'],
        text: string,
    ) => {
        update({ comments: { ...hour.comments, [field]: text } });
    };

    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-xl border bg-card',
                hasError || overBooked
                    ? 'border-destructive shadow-none'
                    : undefined,
                !hasError && !overBooked && locked
                    ? 'border-dashed bg-muted/40 shadow-none'
                    : undefined,
                !hasError && !overBooked && !locked && balanced
                    ? 'border-primary/30 shadow-raised'
                    : undefined,
                !hasError && !overBooked && !locked && !balanced
                    ? 'shadow-tile'
                    : undefined,
            )}
            data-test="hour-row"
            data-locked={locked ? 'true' : 'false'}
        >
            {locked && (
                <div
                    className="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-background/80 px-6 text-center"
                    data-test="hour-locked"
                >
                    <p className="flex max-w-md items-center justify-center gap-2 text-sm font-medium">
                        <Lock className="size-4 shrink-0" />
                        {lockReason}
                    </p>
                </div>
            )}

            <div
                className={cn(
                    'grid items-end gap-3 p-3 sm:grid-cols-[7rem_5rem_5rem_1fr_auto]',
                    locked ? 'pointer-events-none opacity-40' : undefined,
                )}
            >
                <div className="grid gap-1">
                    <span className="text-xs text-muted-foreground">Hora</span>
                    <span className="text-sm font-medium tabular-nums">
                        {hour.hour_range}
                    </span>
                    {hour.sku !== '' && (
                        <span className="text-xs text-muted-foreground">
                            SKU {hour.sku}
                        </span>
                    )}
                    {duration !== 60 && (
                        <span className="text-xs text-muted-foreground">
                            {formatMinutes(duration)}
                        </span>
                    )}
                </div>

                <div className="grid gap-1">
                    <span className="text-xs text-muted-foreground">Meta</span>
                    <span
                        className="text-sm font-medium tabular-nums"
                        data-test="hour-estimado"
                    >
                        {hour.estimado === '' ? '—' : hour.estimado}
                    </span>
                    <InputError message={errors.estimado} />
                </div>

                <div className="grid gap-1">
                    <span className="text-xs text-muted-foreground">Acum.</span>
                    <span
                        className="text-sm font-medium tabular-nums"
                        data-test="hour-acumulado"
                    >
                        {acumulado > 0 ? formatQuantity(acumulado) : '—'}
                    </span>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor={`producido-${hour.hour_index}`}>
                        Producido
                    </Label>
                    <Input
                        id={`producido-${hour.hour_index}`}
                        type="number"
                        min="0"
                        max={maxProduced}
                        step="0.01"
                        inputMode="decimal"
                        value={hour.producido}
                        disabled={locked}
                        onChange={(event) => updateProduced(event.target.value)}
                        data-test="hour-producido"
                    />
                    {maxProduced !== undefined && (
                        <p className="text-xs text-muted-foreground">
                            Máximo {hour.estimado}
                        </p>
                    )}
                    <InputError message={errors.producido} />
                </div>

                <div className="flex items-center gap-1">
                    {canChangeProduct && onChangeProduct && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={locked}
                            onClick={onChangeProduct}
                            data-test="change-product"
                        >
                            Cambio de producto
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={locked}
                        onClick={() => setShowDetail((current) => !current)}
                        aria-expanded={expanded}
                        data-test="hour-detail-toggle"
                    >
                        <ChevronDown
                            className={cn(
                                'transition-transform',
                                expanded ? 'rotate-180' : undefined,
                            )}
                        />
                        {hour.stops.length > 0
                            ? `${hour.stops.length} paradas`
                            : 'Detalle'}
                    </Button>
                </div>
            </div>

            <div
                className={cn(
                    'flex flex-wrap items-center gap-x-4 gap-y-1 border-t px-3 py-2 text-xs',
                    locked ? 'pointer-events-none opacity-40' : undefined,
                )}
            >
                <HourStatusBadge
                    status={statusOf(estimated, produced)}
                    label={STATUS_LABELS[statusOf(estimated, produced)]}
                />
                <span className="text-muted-foreground">
                    Por justificar:{' '}
                    <span className="tabular-nums">
                        {formatMinutes(toJustify)}
                    </span>
                </span>
                <span className="text-muted-foreground">
                    Justificados:{' '}
                    <span className="tabular-nums">
                        {formatMinutes(justified)}
                    </span>
                </span>
                {pending > BALANCE_TOLERANCE_MINUTES && (
                    <span className="font-medium text-status-warning tabular-nums">
                        Faltan {formatMinutes(pending)} por explicar
                    </span>
                )}
                {overBooked && (
                    <span className="font-medium text-destructive">
                        Las paradas superan los {formatMinutes(duration)} de la
                        hora
                    </span>
                )}
                {errors.stops && (
                    <span className="font-medium text-destructive">
                        {errors.stops}
                    </span>
                )}
                {balanced && (
                    <span className="font-medium text-primary">
                        Hora cuadrada. Puedes pasar a la siguiente.
                    </span>
                )}
            </div>

            {expanded && (
                <HourStopsEditor
                    hour={hour}
                    duration={duration}
                    onAddStop={onAddStop}
                    onRemoveStop={removeStop}
                    onUpdateStop={updateStop}
                    onUpdateComment={updateComment}
                />
            )}
        </div>
    );
}
