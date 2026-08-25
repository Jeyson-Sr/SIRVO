import type { RankedStop } from '@/features/oee/types';
import {
    formatMinutes,
    formatPercentage,
    lossColor,
} from '@/features/oee/utils';

type Props = {
    stops: RankedStop[];
};

const WIDTH = 720;
const HEIGHT = 260;
const PADDING = { top: 16, right: 34, bottom: 46, left: 38 };

const PLOT_WIDTH = WIDTH - PADDING.left - PADDING.right;
const PLOT_HEIGHT = HEIGHT - PADDING.top - PADDING.bottom;

/** The share of losses the vital few are expected to explain. */
const PARETO_THRESHOLD = 80;

function cumulativeY(percentage: number): number {
    return PADDING.top + (1 - percentage / 100) * PLOT_HEIGHT;
}

/**
 * Stop codes ranked by downtime, with the cumulative share drawn over them.
 *
 * Reading where the curve crosses eighty percent tells you how many codes you
 * have to fix to remove most of the losses.
 */
export function ParetoChart({ stops }: Props) {
    if (stops.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                No se registraron paradas en el periodo seleccionado.
            </p>
        );
    }

    const slotWidth = PLOT_WIDTH / stops.length;
    const barWidth = Math.min(slotWidth * 0.62, 42);
    const highestMinutes = Math.max(...stops.map((stop) => stop.totalMinutos));

    const slotCenter = (index: number) =>
        PADDING.left + slotWidth * (index + 0.5);

    const curvePoints = stops
        .map(
            (stop, index) =>
                `${slotCenter(index)},${cumulativeY(stop.porcentajeAcumulado)}`,
        )
        .join(' ');

    return (
        <figure className="w-full">
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                className="w-full"
                role="img"
                aria-label="Pareto de códigos de parada"
            >
                {[0, 25, 50, 75, 100].map((percentage) => (
                    <line
                        key={percentage}
                        x1={PADDING.left}
                        x2={PADDING.left + PLOT_WIDTH}
                        y1={cumulativeY(percentage)}
                        y2={cumulativeY(percentage)}
                        className="stroke-border"
                        strokeWidth={1}
                    />
                ))}

                <line
                    x1={PADDING.left}
                    x2={PADDING.left + PLOT_WIDTH}
                    y1={cumulativeY(PARETO_THRESHOLD)}
                    y2={cumulativeY(PARETO_THRESHOLD)}
                    className="stroke-status-warning"
                    strokeWidth={1.5}
                    strokeDasharray="6 4"
                />
                <text
                    x={PADDING.left + PLOT_WIDTH + 4}
                    y={cumulativeY(PARETO_THRESHOLD) + 3}
                    className="fill-status-warning text-[10px] tabular-nums"
                >
                    80%
                </text>

                {stops.map((stop, index) => {
                    const barHeight =
                        highestMinutes > 0
                            ? (stop.totalMinutos / highestMinutes) * PLOT_HEIGHT
                            : 0;

                    return (
                        <rect
                            key={stop.codigo}
                            x={slotCenter(index) - barWidth / 2}
                            y={PADDING.top + PLOT_HEIGHT - barHeight}
                            width={barWidth}
                            height={barHeight}
                            rx={2}
                            style={{ fill: lossColor(stop.tipo) }}
                        >
                            <title>
                                {`${stop.codigo} · ${stop.tipoLabel}: ${formatMinutes(stop.totalMinutos)} (${formatPercentage(stop.porcentaje)})`}
                            </title>
                        </rect>
                    );
                })}

                <polyline
                    points={curvePoints}
                    fill="none"
                    strokeWidth={2}
                    strokeLinejoin="round"
                    className="stroke-foreground"
                />

                {stops.map((stop, index) => (
                    <circle
                        key={stop.codigo}
                        cx={slotCenter(index)}
                        cy={cumulativeY(stop.porcentajeAcumulado)}
                        r={2.5}
                        className="fill-background stroke-foreground"
                        strokeWidth={1.5}
                    >
                        <title>
                            {`Acumulado: ${formatPercentage(stop.porcentajeAcumulado)}`}
                        </title>
                    </circle>
                ))}

                {stops.map((stop, index) => (
                    <text
                        key={stop.codigo}
                        x={slotCenter(index)}
                        y={HEIGHT - 26}
                        textAnchor="end"
                        transform={`rotate(-45 ${slotCenter(index)} ${HEIGHT - 26})`}
                        className="fill-muted-foreground text-[9px]"
                    >
                        {stop.codigo}
                    </text>
                ))}

                {[0, 50, 100].map((percentage) => (
                    <text
                        key={percentage}
                        x={PADDING.left - 8}
                        y={cumulativeY(percentage) + 3}
                        textAnchor="end"
                        className="fill-muted-foreground text-[10px] tabular-nums"
                    >
                        {percentage}
                    </text>
                ))}
            </svg>
        </figure>
    );
}
