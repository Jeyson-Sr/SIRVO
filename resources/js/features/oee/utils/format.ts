import type { HourStatusValue, StopTypeValue } from '@/features/oee/types';

/** Loss families in the order they are always presented. */
export const LOSS_FAMILIES: StopTypeValue[] = [
    'EQ',
    'OPD',
    'OR',
    'PD',
    'QD',
    'RD',
    'TNP',
];

const LOSS_LABELS: Record<StopTypeValue, string> = {
    EQ: 'Equipo',
    OPD: 'Operativas',
    OR: 'Organizacionales',
    PD: 'Planificadas',
    QD: 'Calidad',
    RD: 'Rutinarias',
    TNP: 'No programado',
};

/**
 * Theme tokens rather than literal colours, so a family keeps its identity
 * across light and dark mode and can be restyled from the stylesheet alone.
 */
const LOSS_TOKENS: Record<StopTypeValue, string> = {
    EQ: 'var(--loss-eq)',
    OPD: 'var(--loss-opd)',
    OR: 'var(--loss-or)',
    PD: 'var(--loss-pd)',
    QD: 'var(--loss-qd)',
    RD: 'var(--loss-rd)',
    TNP: 'var(--loss-tnp)',
};

const STATUS_TOKENS: Record<HourStatusValue, string> = {
    pending: 'var(--status-pending)',
    on_target: 'var(--status-ok)',
    warning: 'var(--status-warning)',
    critical: 'var(--status-critical)',
};

export function lossLabel(family: StopTypeValue): string {
    return LOSS_LABELS[family];
}

export function lossColor(family: StopTypeValue): string {
    return LOSS_TOKENS[family];
}

export function statusColor(status: HourStatusValue): string {
    return STATUS_TOKENS[status];
}

/**
 * Colour an OEE figure by how far it sits from the target.
 *
 * Eighty-five percent is the shop-floor target; below seventy is treated as
 * needing intervention rather than merely watching.
 */
export function oeeColor(percentage: number): string {
    if (percentage >= 85) {
        return 'var(--status-ok)';
    }

    return percentage >= 70
        ? 'var(--status-warning)'
        : 'var(--status-critical)';
}

const decimalFormatter = new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
});

const wholeFormatter = new Intl.NumberFormat('es-PE', {
    maximumFractionDigits: 0,
});

export function formatPercentage(value: number): string {
    return `${decimalFormatter.format(value)}%`;
}

export function formatMinutes(value: number): string {
    return `${decimalFormatter.format(value)} min`;
}

export function formatNumber(value: number): string {
    return wholeFormatter.format(value);
}

/** Pallet-scale quantities: one decimal when it is not a whole number. */
export function formatQuantity(value: number): string {
    return decimalFormatter.format(value);
}

/**
 * Render minutes as hours and minutes once they stop being easy to read.
 */
export function formatDuration(minutes: number): string {
    if (minutes < 60) {
        return formatMinutes(minutes);
    }

    const wholeHours = Math.floor(minutes / 60);
    const remainder = Math.round(minutes % 60);

    return remainder === 0
        ? `${wholeHours} h`
        : `${wholeHours} h ${remainder} min`;
}

/** Format an ISO date as the short day/month used across the module. */
export function formatDate(isoDate: string): string {
    const [year, month, day] = isoDate.split('-');

    return `${day}/${month}/${year}`;
}
