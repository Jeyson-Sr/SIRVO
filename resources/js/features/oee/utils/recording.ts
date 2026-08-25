import type {
    HourDraft,
    ProductionDraft,
    ShiftValue,
} from '@/features/oee/types';

function emptyHourSheet(): Pick<
    HourDraft,
    'duration_minutes' | 'sku' | 'formato' | 'pallets_por_hora' | 'bph'
> {
    return {
        duration_minutes: 60,
        sku: '',
        formato: '',
        pallets_por_hora: '',
        bph: '',
    };
}

export function buildHours(
    ranges: string[],
    target = '',
    sheet: Partial<HourDraft> = {},
): HourDraft[] {
    const defaults = emptyHourSheet();

    return ranges.map((range, index) => ({
        hour_index: index,
        hour_range: range,
        duration_minutes: sheet.duration_minutes ?? defaults.duration_minutes,
        sku: sheet.sku ?? defaults.sku,
        formato: sheet.formato ?? defaults.formato,
        pallets_por_hora: sheet.pallets_por_hora ?? target,
        bph: sheet.bph ?? defaults.bph,
        estimado: target,
        producido: '',
        closed: false,
        comments: { mnf: '', mantto: '', calidad: '' },
        stops: [],
    }));
}

export function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export function isShiftHeaderComplete(production: ProductionDraft): boolean {
    return (
        production.fecha.trim() !== '' &&
        production.linea.trim() !== '' &&
        production.op.trim() !== '' &&
        production.sku.trim() !== '' &&
        production.ingeniero.trim() !== '' &&
        production.operador.trim() !== ''
    );
}

export function defaultShiftValue(): ShiftValue {
    return 'DIA';
}
