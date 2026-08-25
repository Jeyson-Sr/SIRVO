import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import type {
    HourDraft,
    RecordingPayload,
    SelectOption,
    ShiftValue,
    SkuOption,
    StopCode,
    StopDraft,
} from '@/features/oee/types';
import {
    accountHour,
    buildHours,
    isHourBalanced,
    isHourStarted,
    isHourVisible,
    isShiftHeaderComplete,
    midpointClock,
    newClientUuid,
    palletTarget,
    splitHourAt,
    today,
} from '@/features/oee/utils';
import * as productions from '@/routes/oee/productions';

export type RecordingFormProps = {
    shifts: SelectOption[];
    lines: string[];
    hourRanges: Record<ShiftValue, string[]>;
    ingeniero: string;
    productionId?: number;
    isClosed?: boolean;
    recording?: RecordingPayload;
};

export type PendingContinuation = {
    hourIndex: number;
    code: StopCode;
    minutes: number;
    frequency: number;
    previous: StopDraft;
    previousRange: string;
};

export type PendingProductChange = {
    hourIndex: number;
};

export function useRecordingForm({
    lines,
    hourRanges,
    ingeniero,
    productionId,
    recording,
}: RecordingFormProps) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';
    const engineerName = usePage().props.auth.user.name || ingeniero;
    const isEditing = productionId !== undefined;

    const [hourAddingStop, setHourAddingStop] = useState<number | null>(null);
    const [continuation, setContinuation] =
        useState<PendingContinuation | null>(null);
    const [productChange, setProductChange] =
        useState<PendingProductChange | null>(null);
    const [cutTime, setCutTime] = useState('');
    const [nextSkuCode, setNextSkuCode] = useState('');
    const [nextSku, setNextSku] = useState<SkuOption | null>(null);
    const [productChangeError, setProductChangeError] = useState('');

    const { data, setData, post, put, processing, errors } =
        useForm<RecordingPayload>(
            recording ?? {
                production: {
                    fecha: today(),
                    turno: 'DIA',
                    linea: lines[0] ?? '',
                    op: '',
                    ingeniero: engineerName,
                    operador: '',
                    sku: '',
                    descripcion: '',
                    formato: '',
                    marca: '',
                    sabor: '',
                    pallets_por_hora: '',
                    bph: '',
                },
                hours: buildHours(hourRanges.DIA),
            },
        );

    const applySku = (sku: SkuOption) => {
        const target = String(sku.palletsPorHora);

        setData((previous) => ({
            production: {
                ...previous.production,
                sku: sku.sku,
                descripcion: sku.descripcion,
                formato: sku.formato ?? '',
                marca: sku.marca ?? '',
                sabor: sku.sabor ?? '',
                pallets_por_hora: target,
                bph: String(sku.bph),
            },
            hours: previous.hours.map((hour) => {
                if (isHourStarted(hour) && hour.sku !== '') {
                    return hour;
                }

                return {
                    ...hour,
                    sku: sku.sku,
                    formato: sku.formato ?? '',
                    pallets_por_hora: target,
                    bph: String(sku.bph),
                    estimado: String(
                        palletTarget(sku.palletsPorHora, hour.duration_minutes),
                    ),
                };
            }),
        }));
    };

    const changeShift = (turno: ShiftValue) => {
        setData((previous) => ({
            ...previous,
            production: { ...previous.production, turno },
            hours: previous.hours.map((hour, index) => ({
                ...hour,
                hour_range: hourRanges[turno][index] ?? hour.hour_range,
            })),
        }));
    };

    const changeLine = (linea: string) => {
        setData((previous) => {
            if (previous.production.linea === linea) {
                return previous;
            }

            return {
                production: {
                    ...previous.production,
                    linea,
                    sku: '',
                    descripcion: '',
                    formato: '',
                    marca: '',
                    sabor: '',
                    pallets_por_hora: '',
                    bph: '',
                },
                hours: previous.hours.map((hour) => {
                    if (isHourStarted(hour) && hour.sku !== '') {
                        return hour;
                    }

                    return {
                        ...hour,
                        sku: '',
                        formato: '',
                        pallets_por_hora: '',
                        bph: '',
                        estimado: '',
                    };
                }),
            };
        });
    };

    const appendStop = (hourIndex: number, stop: StopDraft) => {
        setData((previous) => ({
            ...previous,
            hours: previous.hours.map((hour, index) => {
                if (index !== hourIndex) {
                    return hour;
                }

                const next = { ...hour, stops: [...hour.stops, stop] };

                return { ...next, closed: isHourBalanced(next) };
            }),
        }));
    };

    const makeStop = (
        code: StopCode,
        minutes: number,
        frequency: number,
        continua: boolean,
        comentario: string,
    ): StopDraft => ({
        client_uuid: newClientUuid(),
        codigo: code.codigo,
        tipo: code.tipo,
        descripcion: code.detalle ?? code.tipoLabel,
        comentario,
        tiempo_minutos: minutes,
        frecuencia: continua ? 0 : frequency,
        continua,
        registered_at: new Date().toISOString(),
    });

    const previousStopFor = (
        hourIndex: number,
        codigo: string,
    ): StopDraft | null => {
        if (hourIndex <= 0) {
            return null;
        }

        const matches = data.hours[hourIndex - 1].stops.filter(
            (stop) => stop.codigo.toUpperCase() === codigo.toUpperCase(),
        );

        return matches.at(-1) ?? null;
    };

    const addStop = (code: StopCode, minutes: number, frequency: number) => {
        if (hourAddingStop === null) {
            return;
        }

        const hourIndex = hourAddingStop;
        const previous = previousStopFor(hourIndex, code.codigo);

        if (previous) {
            setContinuation({
                hourIndex,
                code,
                minutes,
                frequency,
                previous,
                previousRange: data.hours[hourIndex - 1].hour_range,
            });

            return;
        }

        appendStop(hourIndex, makeStop(code, minutes, frequency, false, ''));
    };

    const resolveContinuation = (isContinuous: boolean) => {
        if (continuation === null) {
            return;
        }

        appendStop(
            continuation.hourIndex,
            makeStop(
                continuation.code,
                continuation.minutes,
                continuation.frequency,
                isContinuous,
                isContinuous ? continuation.previous.comentario : '',
            ),
        );
        setContinuation(null);
    };

    const openProductChange = (hourIndex: number) => {
        setProductChange({ hourIndex });
        setCutTime(midpointClock(data.hours[hourIndex].hour_range));
        setNextSkuCode('');
        setNextSku(null);
        setProductChangeError('');
    };

    const applyProductChange = () => {
        if (productChange === null || nextSku === null) {
            setProductChangeError('Elige el SKU que entra después del corte.');

            return;
        }

        const hourIndex = productChange.hourIndex;
        const current = data.hours[hourIndex];
        const previousPallets = Number.parseFloat(
            current.pallets_por_hora || data.production.pallets_por_hora,
        );

        try {
            const parts = splitHourAt(
                current.hour_range,
                cutTime,
                Number.isFinite(previousPallets) ? previousPallets : 0,
                nextSku.palletsPorHora,
            );

            const beforeHour: HourDraft = {
                ...current,
                hour_range: parts.before.hour_range,
                duration_minutes: parts.before.duration_minutes,
                estimado: String(parts.before.estimado),
                sku: current.sku || data.production.sku,
                formato: current.formato || data.production.formato,
                pallets_por_hora:
                    current.pallets_por_hora ||
                    data.production.pallets_por_hora,
                bph: current.bph || data.production.bph,
                closed: isHourBalanced({
                    ...current,
                    estimado: String(parts.before.estimado),
                    duration_minutes: parts.before.duration_minutes,
                }),
            };

            const afterHour: HourDraft = {
                hour_index: hourIndex + 1,
                hour_range: parts.after.hour_range,
                duration_minutes: parts.after.duration_minutes,
                sku: nextSku.sku,
                formato: nextSku.formato ?? '',
                pallets_por_hora: String(nextSku.palletsPorHora),
                bph: String(nextSku.bph),
                estimado: String(parts.after.estimado),
                producido: '',
                closed: false,
                comments: { mnf: '', mantto: '', calidad: '' },
                stops: [],
            };

            const rest = data.hours.slice(hourIndex + 1).map((hour, offset) => {
                const nextIndex = hourIndex + 2 + offset;

                if (isHourStarted(hour)) {
                    return { ...hour, hour_index: nextIndex };
                }

                return {
                    ...hour,
                    hour_index: nextIndex,
                    sku: nextSku.sku,
                    formato: nextSku.formato ?? '',
                    pallets_por_hora: String(nextSku.palletsPorHora),
                    bph: String(nextSku.bph),
                    estimado: String(
                        palletTarget(
                            nextSku.palletsPorHora,
                            hour.duration_minutes,
                        ),
                    ),
                };
            });

            setData((previous) => ({
                production: {
                    ...previous.production,
                    sku: nextSku.sku,
                    descripcion: nextSku.descripcion,
                    formato: nextSku.formato ?? '',
                    marca: nextSku.marca ?? '',
                    sabor: nextSku.sabor ?? '',
                    pallets_por_hora: String(nextSku.palletsPorHora),
                    bph: String(nextSku.bph),
                },
                hours: [
                    ...previous.hours.slice(0, hourIndex),
                    beforeHour,
                    afterHour,
                    ...rest,
                ],
            }));

            setProductChange(null);
            setProductChangeError('');
        } catch (error) {
            setProductChangeError(
                error instanceof Error
                    ? error.message
                    : 'La hora de cambio no es válida.',
            );
        }
    };

    const pendingMinutes = (hour: HourDraft): number =>
        accountHour(hour).pending;

    const hourForPicker =
        hourAddingStop === null ? null : data.hours[hourAddingStop];

    const headerReady = isShiftHeaderComplete(data.production);
    const lastVisibleHourIndex = data.hours.reduce(
        (last, _hour, index) =>
            isHourVisible(index, data.hours) ? index : last,
        0,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEditing && productionId !== undefined) {
            put(productions.update.url([teamSlug, productionId]));

            return;
        }

        post(productions.store.url(teamSlug));
    };

    return {
        teamSlug,
        isEditing,
        data,
        setData,
        processing,
        errors,
        hourAddingStop,
        setHourAddingStop,
        continuation,
        setContinuation,
        productChange,
        setProductChange,
        cutTime,
        setCutTime,
        nextSkuCode,
        setNextSkuCode,
        nextSku,
        setNextSku,
        productChangeError,
        applySku,
        changeShift,
        changeLine,
        addStop,
        resolveContinuation,
        openProductChange,
        applyProductChange,
        pendingMinutes,
        hourForPicker,
        headerReady,
        lastVisibleHourIndex,
        submit,
    };
}
