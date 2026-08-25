/** Pallet target for a slice: hourly PH scaled to the minutes it ran. */
export function palletTarget(
    palletsPerHour: number,
    durationMinutes: number,
): number {
    if (palletsPerHour <= 0 || durationMinutes <= 0) {
        return 0;
    }

    return Math.round(((palletsPerHour * durationMinutes) / 60) * 100) / 100;
}

function minutesFromClock(clock: string): number {
    const [hours, minutes] = clock.slice(0, 5).split(':').map(Number);

    return hours * 60 + minutes;
}

function elapsedMinutes(from: number, to: number): number {
    const minutes = to - from;

    return minutes > 0 ? minutes : minutes + 1440;
}

function endsOfRange(range: string): [string, string] {
    const [from, to] = range.split('-').map((part) => part.trim());

    return [from, to];
}

export type HourSplit = {
    hour_range: string;
    duration_minutes: number;
    estimado: number;
};

/**
 * Split a clock-hour slot at the minute a product change happens.
 *
 * Mirrors App\Modules\Oee\Actions\SplitHourAt so the operator sees the cut
 * before saving. The stored slices are always recomputed on the server.
 */
export function splitHourAt(
    hourRange: string,
    cutTime: string,
    palletsBefore: number,
    palletsAfter: number,
): { before: HourSplit; after: HourSplit } {
    const [from, to] = endsOfRange(hourRange);
    const start = minutesFromClock(from);
    const end = minutesFromClock(to);
    const cut = minutesFromClock(cutTime);
    const slotMinutes = elapsedMinutes(start, end);
    const beforeMinutes = elapsedMinutes(start, cut);

    if (beforeMinutes <= 0 || beforeMinutes >= slotMinutes) {
        throw new Error(
            'La hora de cambio debe quedar dentro del rango de la hora.',
        );
    }

    const afterMinutes = slotMinutes - beforeMinutes;
    const normalizedCut = cutTime.slice(0, 5);

    return {
        before: {
            hour_range: `${from} - ${normalizedCut}`,
            duration_minutes: beforeMinutes,
            estimado: palletTarget(palletsBefore, beforeMinutes),
        },
        after: {
            hour_range: `${normalizedCut} - ${to}`,
            duration_minutes: afterMinutes,
            estimado: palletTarget(palletsAfter, afterMinutes),
        },
    };
}

export function midpointClock(hourRange: string): string {
    const [from, to] = endsOfRange(hourRange);
    const start = minutesFromClock(from);
    const mid =
        (start + Math.floor(elapsedMinutes(start, minutesFromClock(to)) / 2)) %
        1440;
    const hours = Math.floor(mid / 60)
        .toString()
        .padStart(2, '0');
    const minutes = (mid % 60).toString().padStart(2, '0');

    return `${hours}:${minutes}`;
}
