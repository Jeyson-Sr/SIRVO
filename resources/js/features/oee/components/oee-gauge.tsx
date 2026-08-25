import { formatPercentage, oeeColor } from '@/features/oee/utils';
import { cn } from '@/lib/utils';

type Props = {
    value: number;
    label: string;
    caption?: string;
    className?: string;
};

const CENTER_X = 100;
const CENTER_Y = 100;
const RADIUS = 78;
const TRACK_WIDTH = 16;

/**
 * Convert a point on the gauge arc to canvas coordinates.
 *
 * The arc runs from 180 degrees (left) to 360 degrees (right), so the top of
 * the half circle sits at 270 degrees.
 */
function pointAt(degrees: number): { x: number; y: number } {
    const radians = (degrees * Math.PI) / 180;

    return {
        x: CENTER_X + RADIUS * Math.cos(radians),
        y: CENTER_Y + RADIUS * Math.sin(radians),
    };
}

function arcPath(percentage: number): string {
    const start = pointAt(180);
    const end = pointAt(180 + (percentage / 100) * 180);

    return `M ${start.x} ${start.y} A ${RADIUS} ${RADIUS} 0 0 1 ${end.x} ${end.y}`;
}

/**
 * A half-circle gauge for a single percentage figure.
 */
export function OeeGauge({ value, label, caption, className }: Props) {
    const bounded = Math.max(0, Math.min(100, value));

    return (
        <figure className={cn('flex flex-col items-center', className)}>
            <svg
                viewBox="0 0 200 118"
                className="w-full max-w-[240px]"
                role="img"
                aria-label={`${label}: ${formatPercentage(value)}`}
            >
                <path
                    d={arcPath(100)}
                    fill="none"
                    strokeWidth={TRACK_WIDTH}
                    strokeLinecap="round"
                    className="stroke-muted"
                />

                {bounded > 0 && (
                    <path
                        d={arcPath(bounded)}
                        fill="none"
                        strokeWidth={TRACK_WIDTH}
                        strokeLinecap="round"
                        style={{ stroke: oeeColor(bounded) }}
                    />
                )}

                <text
                    x={CENTER_X}
                    y={CENTER_Y - 14}
                    textAnchor="middle"
                    className="fill-foreground text-[28px] font-semibold tabular-nums"
                >
                    {formatPercentage(value)}
                </text>
            </svg>

            <figcaption className="-mt-2 text-center">
                <p className="text-sm font-medium text-foreground">{label}</p>
                {caption && (
                    <p className="text-xs text-muted-foreground">{caption}</p>
                )}
            </figcaption>
        </figure>
    );
}
