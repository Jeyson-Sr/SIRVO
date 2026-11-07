import { useId } from 'react';
import { formatPercentage } from '@/features/oee/utils';

export type TrendPoint = {
    label: string;
    oee: number;
    em: number;
};

type Props = {
    points: TrendPoint[];
};

const WIDTH = 720;
const HEIGHT = 240;
const PADDING = { top: 16, right: 16, bottom: 28, left: 36 };

const PLOT_WIDTH = WIDTH - PADDING.left - PADDING.right;
const PLOT_HEIGHT = HEIGHT - PADDING.top - PADDING.bottom;

const GRID_PERCENTAGES = [0, 25, 50, 75, 100];

/**
 * Spread the points evenly across the plot; a lone point sits in the middle.
 */
function horizontalPosition(index: number, total: number): number {
    if (total === 1) {
        return PADDING.left + PLOT_WIDTH / 2;
    }

    return PADDING.left + (index / (total - 1)) * PLOT_WIDTH;
}

function verticalPosition(percentage: number): number {
    return PADDING.top + (1 - percentage / 100) * PLOT_HEIGHT;
}

function polyline(values: number[]): string {
    return values
        .map(
            (value, index) =>
                `${horizontalPosition(index, values.length)},${verticalPosition(value)}`,
        )
        .join(' ');
}

/**
 * Two series plotted over time: overall OEE and equipment availability.
 */
export function TrendChart({ points }: Props) {
    const gradientId = useId();

    if (points.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                No hay horas cerradas en el periodo seleccionado.
            </p>
        );
    }

    const oeeValues = points.map((point) => point.oee);
    const emValues = points.map((point) => point.em);

    // Closing the OEE line back down to the baseline turns it into a fillable area.
    const areaPath = `${polyline(oeeValues)} ${PADDING.left + PLOT_WIDTH},${PADDING.top + PLOT_HEIGHT} ${PADDING.left},${PADDING.top + PLOT_HEIGHT}`;

    // A crowded axis is unreadable, so labels thin out as points are added.
    const labelStep = Math.ceil(points.length / 12);

    return (
        <figure className="w-full">
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                className="w-full"
                role="img"
                aria-label="Evolución de OEE y EM"
            >
                <defs>
                    <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                        <stop
                            offset="0%"
                            stopColor="var(--primary)"
                            stopOpacity="0.25"
                        />
                        <stop
                            offset="100%"
                            stopColor="var(--primary)"
                            stopOpacity="0"
                        />
                    </linearGradient>
                </defs>

                {GRID_PERCENTAGES.map((percentage) => (
                    <g key={percentage}>
                        <line
                            x1={PADDING.left}
                            x2={PADDING.left + PLOT_WIDTH}
                            y1={verticalPosition(percentage)}
                            y2={verticalPosition(percentage)}
                            className="stroke-border"
                            strokeWidth={1}
                        />
                        <text
                            x={PADDING.left - 8}
                            y={verticalPosition(percentage) + 4}
                            textAnchor="end"
                            className="fill-muted-foreground text-[10px] tabular-nums"
                        >
                            {percentage}
                        </text>
                    </g>
                ))}

                <polygon fill={`url(#${gradientId})`} points={areaPath} />

                <polyline
                    points={polyline(emValues)}
                    fill="none"
                    strokeWidth={2}
                    strokeDasharray="5 4"
                    strokeLinejoin="round"
                    className="stroke-muted-foreground"
                />

                <polyline
                    points={polyline(oeeValues)}
                    fill="none"
                    strokeWidth={2.5}
                    strokeLinejoin="round"
                    strokeLinecap="round"
                    className="stroke-primary"
                />

                {points.map((point, index) => (
                    <circle
                        key={point.label + index}
                        cx={horizontalPosition(index, points.length)}
                        cy={verticalPosition(point.oee)}
                        r={3}
                        className="fill-primary"
                    >
                        <title>{`${point.label}: ${formatPercentage(point.oee)}`}</title>
                    </circle>
                ))}

                {points.map((point, index) =>
                    index % labelStep === 0 ? (
                        <text
                            key={point.label + index}
                            x={horizontalPosition(index, points.length)}
                            y={HEIGHT - 8}
                            textAnchor="middle"
                            className="fill-muted-foreground text-[10px]"
                        >
                            {point.label}
                        </text>
                    ) : null,
                )}
            </svg>

            <figcaption className="mt-2 flex items-center justify-center gap-6 text-xs text-muted-foreground">
                <span className="flex items-center gap-2">
                    <span className="h-0.5 w-5 rounded bg-primary" />
                    OEE
                </span>
                <span className="flex items-center gap-2">
                    <span className="h-0.5 w-5 rounded bg-muted-foreground" />
                    EM (disponibilidad de equipo)
                </span>
            </figcaption>
        </figure>
    );
}
