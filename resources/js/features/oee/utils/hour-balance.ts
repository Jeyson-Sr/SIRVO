import type { HourStatusValue } from '@/features/oee/types';

/** Minutes contained in one hour slot. */
export const MINUTES_PER_HOUR = 60;

export const MAX_HOUR_SLICES = 16;

/**
 * Leftover minutes that still count as squared. Matches the one-decimal
 * figures on screen, so a displayed "0 min" unlocks the next hour.
 */
export const BALANCE_TOLERANCE_MINUTES = 0.1;

export const HEADER_HOUR_LOCK_MESSAGE =
    'Completa el turno (OP, SKU y operador) para registrar lo producido.';

export const PREVIOUS_HOUR_LOCK_MESSAGE =
    'Por favor cuadra los tiempos de la hora anterior.';

/**
 * Minutes of downtime implied by an hour falling short of its target.
 *
 * Mirrors the server so the operator sees how much is still unexplained while
 * typing. The stored figure is always recomputed server-side from the same
 * rule, so this copy can never become the source of truth.
 */
export function shortfallMinutes(
    estimated: number,
    produced: number | null,
    durationMinutes = MINUTES_PER_HOUR,
): number {
    if (
        produced === null ||
        estimated <= 0 ||
        produced >= estimated ||
        durationMinutes <= 0
    ) {
        return 0;
    }

    return (
        Math.round(
            ((estimated - produced) / estimated) * durationMinutes * 100,
        ) / 100
    );
}

type HourFigures = {
    estimado: string;
    producido: string;
    duration_minutes?: number;
    stops: { tiempo_minutos: number }[];
};

/** Running totals for one hour while the operator is still typing. */
export function accountHour(hour: HourFigures): {
    estimated: number;
    produced: number | null;
    duration: number;
    toJustify: number;
    justified: number;
    pending: number;
    overBooked: boolean;
} {
    const estimated = Number.parseFloat(hour.estimado);
    const produced =
        hour.producido === '' ? null : Number.parseFloat(hour.producido);
    const duration = hour.duration_minutes ?? MINUTES_PER_HOUR;
    const safeEstimated = Number.isFinite(estimated) ? estimated : 0;
    const safeProduced =
        produced !== null && Number.isFinite(produced) ? produced : null;
    const toJustify = shortfallMinutes(safeEstimated, safeProduced, duration);
    const justified = hour.stops.reduce(
        (total, stop) => total + stop.tiempo_minutos,
        0,
    );

    return {
        estimated: safeEstimated,
        produced: safeProduced,
        duration,
        toJustify,
        justified,
        pending: Math.max(0, Math.round((toJustify - justified) * 100) / 100),
        overBooked: justified > duration,
    };
}

/**
 * An hour unlocks the next slot when output is recorded and every shortfall
 * minute is explained without exceeding the hour.
 */
export function isHourBalanced(hour: HourFigures): boolean {
    const { produced, pending, overBooked } = accountHour(hour);

    return (
        produced !== null && pending <= BALANCE_TOLERANCE_MINUTES && !overBooked
    );
}

/** Whether the operator has already started recording this hour. */
export function isHourStarted(hour: HourFigures): boolean {
    return hour.producido !== '' || hour.stops.length > 0;
}

/**
 * Why an hour cannot be edited. Started later hours stay on screen but lock
 * when an earlier slot is un-squared; unstarted hours are hidden instead.
 */
export function hourLockReason(
    index: number,
    hours: HourFigures[],
    headerReady: boolean,
): string | null {
    if (!headerReady) {
        return HEADER_HOUR_LOCK_MESSAGE;
    }

    if (
        index > 0 &&
        hours.slice(0, index).some((previous) => !isHourBalanced(previous))
    ) {
        return PREVIOUS_HOUR_LOCK_MESSAGE;
    }

    return null;
}

/**
 * Show the first hour, every hour already started, and the next empty slot
 * once every previous hour is squared.
 */
export function isHourVisible(index: number, hours: HourFigures[]): boolean {
    if (index === 0) {
        return true;
    }

    if (isHourStarted(hours[index])) {
        return true;
    }

    return hours.slice(0, index).every((previous) => isHourBalanced(previous));
}

/**
 * Running produced total from the start of the shift through the given hour.
 */
export function accumulatedProduced(
    hours: { producido: string }[],
    throughIndex: number,
): number {
    return hours.slice(0, throughIndex + 1).reduce((total, hour) => {
        if (hour.producido === '') {
            return total;
        }

        const produced = Number.parseFloat(hour.producido);

        return total + (Number.isFinite(produced) ? produced : 0);
    }, 0);
}

/** Mirror of the server rule that grades an hour against its target. */
export function statusOf(
    estimated: number,
    produced: number | null,
): HourStatusValue {
    if (produced === null || estimated <= 0) {
        return 'pending';
    }

    const attainment = produced / estimated;

    if (attainment >= 1) {
        return 'on_target';
    }

    return attainment >= 0.8 ? 'warning' : 'critical';
}

export const STATUS_LABELS: Record<HourStatusValue, string> = {
    pending: 'Sin registrar',
    on_target: 'En meta',
    warning: 'En riesgo',
    critical: 'Crítico',
};
