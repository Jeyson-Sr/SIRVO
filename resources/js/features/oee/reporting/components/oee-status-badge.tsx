import { oeeStatus } from '@/features/oee/utils';

type Props = {
    value: number;
};

/**
 * Names the band an OEE figure falls in, in the same colour as its gauge, so
 * the state is readable without relying on colour alone.
 */
export function OeeStatusBadge({ value }: Props) {
    const { label, color } = oeeStatus(value);

    return (
        <span
            className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium"
            style={{
                color,
                borderColor: `color-mix(in oklab, ${color} 35%, transparent)`,
                backgroundColor: `color-mix(in oklab, ${color} 14%, transparent)`,
            }}
            data-test="oee-status"
        >
            <span
                className="size-1.5 rounded-full"
                style={{ backgroundColor: color }}
                aria-hidden
            />
            {label}
        </span>
    );
}
