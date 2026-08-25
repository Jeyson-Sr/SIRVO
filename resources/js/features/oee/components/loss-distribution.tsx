import type { LossBreakdown } from '@/features/oee/types';
import {
    formatDuration,
    formatPercentage,
    LOSS_FAMILIES,
    lossColor,
    lossLabel,
} from '@/features/oee/utils';

type Props = {
    lossMinutes: LossBreakdown;
    lossImpact: LossBreakdown;
};

/**
 * How the lost time splits across the OEE families.
 *
 * Unscheduled time is listed apart because it shrinks the measured window
 * instead of counting against the result, and mixing it in would overstate
 * the losses.
 */
export function LossDistribution({ lossMinutes, lossImpact }: Props) {
    const families = LOSS_FAMILIES.filter((family) => family !== 'TNP')
        .map((family) => ({
            family,
            minutes: lossMinutes[family] ?? 0,
            impact: lossImpact[family] ?? 0,
        }))
        .sort((a, b) => b.minutes - a.minutes);

    const totalMinutes = families.reduce(
        (total, entry) => total + entry.minutes,
        0,
    );

    if (totalMinutes === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                No se registraron pérdidas en el periodo.
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <div
                className="flex h-3 w-full overflow-hidden rounded-full bg-muted"
                role="img"
                aria-label="Distribución de pérdidas por familia"
            >
                {families
                    .filter((entry) => entry.minutes > 0)
                    .map((entry) => (
                        <div
                            key={entry.family}
                            style={{
                                width: `${(entry.minutes / totalMinutes) * 100}%`,
                                backgroundColor: lossColor(entry.family),
                            }}
                            title={`${lossLabel(entry.family)}: ${formatDuration(entry.minutes)}`}
                        />
                    ))}
            </div>

            <ul className="grid gap-2 sm:grid-cols-2">
                {families.map((entry) => (
                    <li
                        key={entry.family}
                        className="flex items-center justify-between gap-3 text-xs"
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            <span
                                className="size-2.5 shrink-0 rounded-[3px]"
                                style={{
                                    backgroundColor: lossColor(entry.family),
                                }}
                            />
                            <span className="truncate text-muted-foreground">
                                {lossLabel(entry.family)}
                            </span>
                        </span>
                        <span className="shrink-0 tabular-nums">
                            <span className="font-medium text-foreground">
                                {formatDuration(entry.minutes)}
                            </span>
                            <span className="ml-2 text-muted-foreground">
                                {formatPercentage(entry.impact)}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
