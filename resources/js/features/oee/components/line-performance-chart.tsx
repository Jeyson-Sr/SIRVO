import type { LinePerformance } from '@/features/oee/types';
import { formatNumber, formatPercentage, oeeColor } from '@/features/oee/utils';

type Props = {
    lines: LinePerformance[];
};

/**
 * Bars comparing the OEE reached by each production line.
 *
 * Built with CSS rather than SVG so the labels stay crisp and the bars reflow
 * with the container instead of being stretched by a fixed viewBox.
 */
export function LinePerformanceChart({ lines }: Props) {
    if (lines.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Sin líneas con producción registrada.
            </p>
        );
    }

    return (
        <ul className="flex flex-col gap-3">
            {lines.map((line) => (
                <li key={line.linea} className="grid gap-1.5">
                    <div className="flex items-baseline justify-between text-xs">
                        <span className="font-medium text-foreground">
                            {line.linea}
                        </span>
                        <span className="flex items-baseline gap-3 tabular-nums">
                            <span className="text-muted-foreground">
                                {formatNumber(line.volumen)} CU ·{' '}
                                {line.closedHours} h
                            </span>
                            <span className="w-14 text-right text-sm font-semibold text-foreground">
                                {formatPercentage(line.oee)}
                            </span>
                        </span>
                    </div>

                    <div
                        className="h-2.5 w-full overflow-hidden rounded-full bg-muted"
                        role="img"
                        aria-label={`${line.linea}: ${formatPercentage(line.oee)} de OEE`}
                    >
                        <div
                            className="h-full rounded-full transition-[width] duration-500"
                            style={{
                                width: `${Math.min(line.oee, 100)}%`,
                                backgroundColor: oeeColor(line.oee),
                            }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}
