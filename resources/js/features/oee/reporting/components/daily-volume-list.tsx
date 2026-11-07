import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DailyVolumePoint } from '@/features/oee/types';
import { formatDate, formatNumber } from '@/features/oee/utils';
import { cn } from '@/lib/utils';

type Props = {
    days: DailyVolumePoint[];
};

const MONTHS = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

/**
 * Name a full calendar month, or spell out a shorter filtered range.
 */
function periodCaption(days: DailyVolumePoint[]): string {
    if (days.length === 0) {
        return 'Sin días en el periodo';
    }

    const first = days[0].fecha;
    const last = days[days.length - 1].fecha;
    const [startYear, startMonth, startDay] = first.split('-').map(Number);
    const [endYear, endMonth, endDay] = last.split('-').map(Number);
    const lastDayOfMonth = new Date(startYear, startMonth, 0).getDate();
    const coversWholeMonth =
        startYear === endYear &&
        startMonth === endMonth &&
        startDay === 1 &&
        endDay === lastDayOfMonth;

    if (coversWholeMonth) {
        return `${MONTHS[startMonth - 1]} ${startYear}`;
    }

    return `${formatDate(first)} – ${formatDate(last)}`;
}

/**
 * Consumer units for each day of the active period.
 *
 * The period is the date filter when one is set, and the current month otherwise.
 */
export function DailyVolumeList({ days }: Props) {
    const total = days.reduce((sum, day) => sum + day.volumen, 0);

    return (
        <Card className="gap-3 py-4">
            <CardHeader className="px-4">
                <CardTitle className="text-sm">CU por día</CardTitle>
                <CardDescription>{periodCaption(days)}</CardDescription>
            </CardHeader>
            <CardContent className="px-4">
                <ul className="max-h-72 divide-y overflow-y-auto">
                    {days.map((day) => (
                        <li
                            key={day.fecha}
                            className="flex items-center justify-between gap-3 py-1.5 text-sm"
                        >
                            <span className="text-muted-foreground tabular-nums">
                                {day.label}
                            </span>
                            <span
                                className={cn(
                                    'font-medium tabular-nums',
                                    day.volumen === 0 &&
                                        'font-normal text-muted-foreground',
                                )}
                            >
                                {formatNumber(day.volumen)} CU
                            </span>
                        </li>
                    ))}
                </ul>
                <div className="mt-2 flex items-center justify-between gap-3 border-t pt-2 text-sm">
                    <span className="font-medium">Total</span>
                    <span className="font-semibold tabular-nums">
                        {formatNumber(total)} CU
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
